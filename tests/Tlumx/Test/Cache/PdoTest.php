<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Cache;

use Tlumx\Cache\PdoCachePool;

class PdoTest extends CacheTestCase
{
    protected $cacheDriver;
    
    protected $dbh;
    
    public function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension must be loaded.');
        }        
        
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                
        $sql = "CREATE TABLE IF NOT EXISTS cache (";
        $sql .= "id VARBINARY(255) NOT NULL PRIMARY KEY,";
        $sql .= "value BLOB,";
        $sql .= "expire TIMESTAMP,";
        $sql .= "KEY expire";
        $sql .= ")";
        
        $this->dbh->exec($sql);
    }
    
    public function tearDown()
    {
        $this->cacheDriver = null;
        $this->dbh = null;        
    }
    
    protected function getCacheDriver()
    {
        if(!$this->cacheDriver) {            
            $this->cacheDriver = new PdoCachePool($this->dbh);
        }
        
        return $this->cacheDriver;
    }    
}