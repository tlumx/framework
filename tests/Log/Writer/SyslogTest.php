<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Log;

use Tlumx\Log\Writer\Syslog as SyslogWrite;

class SyslogTest extends \PHPUnit_Framework_TestCase
{
    public function testImplements()
    {
        $writer = new SyslogWrite();
        $this->assertInstanceOf('Tlumx\Log\Writer\WriterInterface', $writer);
    }

    public function testLogSyslogWrite()
    {
        $writer = new SyslogWrite();

        $datetime = new \DateTime();
        $writer->write([
            [$datetime, 'error', 500, 'some message']
        ]);
    }
}
