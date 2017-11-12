<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Log\Writer;

use Tlumx\Log\Writer\WriterInterface;

/**
 * PDO lod writer.
 * 
 * Example create for SQLite:
 * CREATE TABLE log (
 *      "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
 *      "level" INTEGER NOT NULL,
 *      "level_name" VARCHAR(255) NOT NULL,
 *      "message" TEXT NOT NULL,
 *      "creation_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
 * );
 */
class PdoWriter implements WriterInterface
{
    /**
     * PDO instance
     *
     * @var \PDO
     */
    protected $dbh;

    /**
     * Table name
     *
     * @var string
     */
    protected $tableName;

    /**
     * Construct
     *
     * @param \PDO $dbh
     * @param string $tableName
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDO $dbh, $tableName = 'log')
    {
        $this->dbh = $dbh;
        if(!is_string($tableName) || empty($tableName)) {
            throw new \InvalidArgumentException("Argument 'tableName' must be not empty string");
        }
        $this->tableName = $tableName;
    }

    /**
     * Write a message to the log
     *
     * @param array $messages
     * @throws \RuntimeException
     */
    public function write(array $messages)
    {
        $stmt = $this->dbh->prepare("INSERT INTO ".$this->dbh->quote($this->tableName)
                . " (level, level_name, message, creation_time)"
                . " VALUES (:level, :level_name, :message, :creation_time)");
        
        foreach ($messages as $record) {
            list($datetime, $level, $levelCode, $message) = $record;            
            $stmt->bindParam(':creation_time', $datetime->getTimestamp(), \PDO::PARAM_INT);
            $stmt->bindParam(':level', $levelCode, \PDO::PARAM_INT);
            $stmt->bindParam(':level_name', $level, \PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, \PDO::PARAM_STR);
            $stmt->execute();
        }
    }
}
