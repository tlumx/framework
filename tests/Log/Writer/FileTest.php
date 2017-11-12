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

use Tlumx\Log\Writer\File as FileWrite;

class FileTest extends \PHPUnit_Framework_TestCase
{
    protected $logDir;

    public function setUp()
    {
        $this->logDir = @tempnam(sys_get_temp_dir(), 'tlumxframework_tmp_log');
        if (!$this->logDir) {
            $e = error_get_last();
            $this->fail("Can't create temporary log directory-file: {$e['message']}");
        } elseif (!@unlink($this->logDir)) {
            $e = error_get_last();
            $this->fail("Can't remove temporary log directory-file: {$e['message']}");
        } elseif (!@mkdir($this->logDir, 0777)) {
            $e = error_get_last();
            $this->fail("Can't create temporary log directory: {$e['message']}");
        }
    }

    public function tearDown()
    {
        testRemoveDirTree($this->logDir);
    }

    public function testImplements()
    {
        $writer = new FileWrite($this->logDir);
        $this->assertInstanceOf('Tlumx\Log\Writer\WriterInterface', $writer);
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidDir()
    {
        $log = new FileWrite('some_invalid_dir');
    }

    public function testLogFileWrite()
    {
        $writer = new FileWrite($this->logDir);

        $datetime = new \DateTime();
        $writer->write([
            [$datetime, 'error', 500, 'some message']
        ]);

        $filename = $this->logDir.DIRECTORY_SEPARATOR.date('Y-m-d').'.log';
        $this->assertTrue(file_exists($filename));

        $lines = file($filename);
        $lastLine = $lines[count($lines) - 1];
        $timestamp = $datetime->format('c');
        $actual = "[$timestamp] error (500): some message".PHP_EOL;

        $this->assertEquals($lastLine, $actual);

        $writer->write([
            [$datetime, 'error', 400, 'some2 message']
        ]);

        $lines = file($filename);
        $lastLine = $lines[count($lines) - 1];
        $actual = "[$timestamp] error (400): some2 message".PHP_EOL;

        $this->assertEquals($lastLine, $actual);
    }
}
