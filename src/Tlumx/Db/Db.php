<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Db;

/**
 * PDO wrapper class.
 */
class Db
{
    /**
     * @var string
     */
    private $_dsn;

    /**
     * @var string
     */
    private $_username;

    /**
     * @var string
     */
    private $_password;

    /**
     * @var array
     */
    private $_driverOptions;

    /**
     * @var \PDO
     */
    private $_dbh;

    /**
     * @var string
     */
    private $_driverName;

    /**
     * @var \Tlumx\Db\DbProfiler
     */
    private $_profiler;

    /**
     * @var bool
     */
    private $_enabledProfiler = false;

    /**
     * Constructor
     *
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array|null $driverOptions
     * @param bool $enabledProfiler
     */
    public function __construct($dsn, $username = null, $password = null, array $driverOptions = null, $enabledProfiler = false)
    {
        $this->_dsn = $dsn;
        $this->_username = $username;
        $this->_password = $password;
        if($driverOptions === null) {
	    $driverOptions = array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
        }
        $this->_driverOptions = $driverOptions;
        $this->setEnabledProfiler($enabledProfiler);
    }

    /**
     * Connect
     *
     * @throws Exception\DbException
     */
    public function connect()
    {
        if($this->_dbh instanceof \PDO) {
            return;
        }
    
        try {
            $key = $this->startProfiler('connection');
            $this->_dbh = new \PDO($this->_dsn, $this->_username, $this->_password, $this->_driverOptions);
            $this->_dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->_driverName =  strtolower($this->_dbh->getAttribute(\PDO::ATTR_DRIVER_NAME));
            $this->endProfiler($key);
        } catch (\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Close connect - disconnect
     */
    public function close()
    {
        $this->_dbh = null;
    }

    /**
     * Is connected
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->_dbh instanceof \PDO) ? true : false;
    }

    /**
     * Return PDO handler
     *
     * @return \PDO
     */
    public function getConnection()
    {
        $this->connect();
        return $this->_dbh;
    }

    /**
     * Set enable use profiler
     */
    public function setEnabledProfiler($enable)
    {
        $this->_enabledProfiler = (boolean) $enable;
    }

    /*
     * Is use profiler
     */
    public function getEnabledProfiler()
    {
        return $this->_enabledProfiler;
    }

    /*
     * Get profiler object
     */
    public function getProfiler()
    {
        if(!$this->_profiler) {
            $this->_profiler = new DbProfiler();
        }
    
        return $this->_profiler;
    }

    /**
     * Start profiler
     *
     * @param string $sql
     * @param mixed $params
     * @return int profiler last key
     */
    public function startProfiler($sql, $params = null)
    {
        if(!$this->_enabledProfiler) {
            return null;
        }
    
        return $this->getProfiler()->start($sql, $params);
    }

    /**
     * End profiler
     *
     * @param int $key
     */
    public function endProfiler($key)
    {
        if(!$this->_enabledProfiler) {
            return;
        }
    
        $this->getProfiler()->end($key);
    }

    /**
     * Get driver name
     *
     * @return string
     */
    public function getDriverName()
    {
        if(is_string($this->_driverName) && !empty($this->_driverName)) {
            return $this->_driverName;
        }
        
        if (($pos = strpos($this->_dsn, ':')) !== false) {
            $this->_driverName = strtolower(substr($this->_dsn, 0, $pos));
            return $this->_driverName;
        }
    
        $this->connect();
        return $this->_driverName;
    }

    /**
     * Quote value
     *
     * @param string $value
     * @return string $value
     */
    public function quoteValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        
        if (($quoteValue = $this->getConnection()->quote($value)) !== false) {
            return $quoteValue;
        }
        
        return "'" . addcslashes(str_replace("'", "''", $value), "\000\n\r\\\032") . "'";
    }

    /**
     * Quote identifier
     *
     * @param string $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {    
        if (strpos($identifier, '.') === false) {
            return $this->doQuoteIdentifier($identifier);
        }
        
        $parts = explode('.', $identifier);
        foreach ($parts as $key => $part) {
            $parts[$key] = $this->doQuoteIdentifier($part);
        }
        return implode('.', $parts);
    }

    /**
     * Make quoted identifier
     *
     * @param string $identifier
     * @return string
     */
    protected function doQuoteIdentifier($identifier)
    {
        if ($identifier === '*') {
            return $identifier;
        }
        switch($this->getDriverName()) {
            case 'sqlsrv':
            case 'mssql':
            case 'dblib':               
                return '[' . $identifier . ']';
            case 'mysql':
            case 'sqlite':
                return '`' . str_replace('`', '``', $identifier) . '`';
            default:
                return '"' . str_replace('"', '\\' . '"', $identifier) . '"';
        }
    }

    /**
     * Begin transaction
     *
     * @return true
     */
    public function beginTransaction()
    {
        $dbh = $this->getConnection();
        $key = $this->startProfiler('begin transaction');
        $dbh->beginTransaction();
        $this->endProfiler($key);
        return true;
    }

    /**
     * Commit
     *
     * @return true
     */
    public function commit()
    {
        $dbh = $this->getConnection();
        $key = $this->startProfiler('commit transaction');
        $dbh->commit();
        $this->endProfiler($key);
        return true;
    }

    /**
     * Rollback
     *
     * @return true
     */
    public function rollBack()
    {
        $dbh = $this->getConnection();
        $key = $this->startProfiler('rollback transaction');
        $dbh->rollBack();
        $this->endProfiler($key);
        return true;
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @param string $name Name of the sequence object
     * @return mixed
     * @throws Exception\DbException
     */
    public function lastInsertId($name = null)
    {
        $result = null;
    
        try {
            $result = $this->getConnection()->lastInsertId($name);
        } catch (\PDOException $e) {
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
        
        return $result;
    }

    /**
     * Wrapper method for PDOStatement::execute()
     *
     * @param string $sqlQuery
     * @param array $params
     * @return int
     * @throws Exception\DbException
     */
    public function execute($sqlQuery, array $params = array())
    {
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($params);
            $count = $sth->rowCount();
            $this->endProfiler($key);
            return $count;
        } catch (\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Wrapper method for PDOStatement::fetchAll()
     *
     * @param string $sqlQuery
     * @param array $params
     * @param int $fetchStyle
     * @return array
     * @throws Exception\DbException
     */
    public function findRows($sqlQuery, array $params = array(), $fetchStyle = \PDO::FETCH_ASSOC)
    {
        $result = null;
    
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($params);
            $result = $sth->fetchAll($fetchStyle);
            $this->endProfiler($key);
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
        
        return $result;
    }

    /**
     * Wrapper method for PDOStatement::fetch()
     *
     * @param string $sqlQuery
     * @param array $params
     * @param int $fetchStyle
     * @return mixed
     * @throws Exception\DbException
     */
    public function findRow($sqlQuery, array $params = array(), $fetchStyle = \PDO::FETCH_ASSOC)
    {
        $result = null;
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($params);
            $result = $sth->fetch($fetchStyle);
            $this->endProfiler($key);
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
        
        return $result;
    }

    /**
     * Wrapper method for PDOStatement::fetchAll(\PDO::FETCH_COLUMN)
     *
     * @param string $sqlQuery
     * @param array $params
     * @return array
     * @throws Exception\DbException
     */
    public function findFirstColumn($sqlQuery, array $params = array())
    {
        $result = null;
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($params);
            $result = $sth->fetchAll(\PDO::FETCH_COLUMN);
            $this->endProfiler($key);
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
        
        return $result;
    }

    /**
     * Wrapper method for PDOStatement::fetch(\PDO::FETCH_NUM)
     *
     * @param string $sqlQuery
     * @param array $params
     * @return mixed
     * @throws Exception\DbException
     */
    public function findOne($sqlQuery, array $params = array())
    {
        $result = null;
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($params);
            $result = $sth->fetch(\PDO::FETCH_NUM);
            $result = $result[0];
            $this->endProfiler($key);
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
        
        return $result;
    }

    /**
     * Insert
     *
     * @param string $table
     * @param array $params
     * @return int
     * @throws Exception\DbException
     */
    public function insert($table, array $params)
    {
        $placeholders = join(", ", array_fill(0, count($params), "?"));
        $fields = array();
        $values = array();
        foreach ($params as $field => $value) {
            $fields[] = $this->quoteIdentifier($field);
            $values[] = $value;
        }
        $fields = join(", ", $fields);
        
        $sqlQuery = "INSERT INTO " . $this->quoteIdentifier($table);
        $sqlQuery .= " (" . $fields . ") VALUES (".$placeholders.")";
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, $params);
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($values);
            $count = $sth->rowCount();
            $this->endProfiler($key);
            return $count;
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update
     *
     * @param string $table
     * @param array $params
     * @param array|string $where
     * @return int
     * @throws Exception\DbException
     */
    public function update($table, array $params, $where = null)
    {
        $updates = array();
        $values = array();
        foreach ($params as $field => $value) {
            $updates[] = $this->quoteIdentifier($field) . " = ?";
            $values[] = $value;
        }
        $updates = implode(', ', $updates);
        
        $sqlWhere = "";
        if($where) {
            if (is_string($where)) {
                $sqlWhere = "WHERE " . $where;
            } elseif(is_array($where)) {
                $cond = array();
                foreach ($where as $field => $value) {
                    if(is_null($value)) {
                        $cond[] = $this->quoteIdentifier($field) . " IS NULL";
                    } else {
                        $cond[] = $this->quoteIdentifier($field) . " = ?";
                        $values[] = $value;
                    }
                }
                $sqlWhere = "WHERE " . implode(' AND ', $cond);
            }
        }
        
        $sqlQuery = "UPDATE " . $this->quoteIdentifier($table) . " SET " . $updates . " " . $sqlWhere;
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, array('params' => $params, 'where' => $where));
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($values);
            $count = $sth->rowCount();
            $this->endProfiler($key);
            return $count;
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Delete
     *
     * @param string $table
     * @param array|string $where
     * @return int
     * @throws Exception\DbException
     */
    public function delete($table, $where = null)
    {
        $values = array();
        $sqlWhere = "";
        if($where) {
            if(is_string($where)) {
                $sqlWhere = "WHERE " . $where;
            } elseif (is_array($where)) {
                $cond = array();
                foreach ($where as $field => $value) {
                    if(is_null($value)) {
                        $cond[] = $this->quoteIdentifier($field) . " IS NULL";
                    } else {
                        $cond[] = $this->quoteIdentifier($field) . " = ?";
                        $values[] = $value;
                    }
                }
                $sqlWhere = "WHERE " . implode(' AND ', $cond);
            }
        }
        
        $sqlQuery = "DELETE FROM " . $this->quoteIdentifier($table) . " " . $sqlWhere;
        
        try {
            $dbh = $this->getConnection();
            $key = $this->startProfiler($sqlQuery, array('where' => $where));
            $sth = $dbh->prepare($sqlQuery);
            $sth->execute($values);
            $count = $sth->rowCount();
            $this->endProfiler($key);
            return $count;
        } catch(\PDOException $e) {
            $this->endProfiler($key);
            throw new Exception\DbException($e->getMessage(), $e->getCode(), $e);
        }
    }
}