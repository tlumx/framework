<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Log;

use Tlumx\Log\Writer\PdoWriter;

class PdoWriterTest extends \PHPUnit_Framework_TestCase
{
    private $dbh;

    protected function setUp()
    {
        $this->dbh = new \PDO('sqlite::memory:');
        $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = 'CREATE TABLE log ('
                . '"id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, '
                . '"level" INTEGER NOT NULL, '
                . '"level_name" VARCHAR(255) NOT NULL, '
                . '"message" TEXT NOT NULL, '
                . '"creation_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
                . ')';

        $this->dbh->exec($sql);
    }

    public function tearDown()
    {
        unset($this->dbh);
    }

    public function testImplements()
    {
        $writer = new PdoWriter($this->dbh);
        $this->assertInstanceOf('Tlumx\Log\Writer\WriterInterface', $writer);
    }

    public function testLogWrite()
    {
        $writer = new PdoWriter($this->dbh, 'log');
        $datetime = new \DateTime();
        $writer->write([
            [$datetime, 'error', 500, 'some error message'],
            [$datetime, 'warning', 400, 'some worning message']
        ]);

        $sth = $this->dbh->prepare("SELECT * FROM log");
        $sth->execute();
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(500, $result[0]['level']);
        $this->assertEquals('error', $result[0]['level_name']);
        $this->assertEquals('some error message', $result[0]['message']);
        $this->assertEquals($datetime->getTimestamp(), $result[0]['creation_time']);

        $this->assertEquals(400, $result[1]['level']);
        $this->assertEquals('warning', $result[1]['level_name']);
        $this->assertEquals('some worning message', $result[1]['message']);
        $this->assertEquals($datetime->getTimestamp(), $result[1]['creation_time']);
    }
}
