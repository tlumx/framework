<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Cache;

use Tlumx\Cache\CacheItem;

class CacheItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheItem
     */
    protected $item;

    protected function setUp()
    {
        $this->item = new CacheItem('my_key', 3600);
    }

    public function testImplements()
    {
        $this->assertInstanceOf("Psr\\Cache\\CacheItemInterface", $this->item);
    }

    public function testGetKey()
    {
        $this->assertEquals('my_key', $this->item->getKey());
    }

    public function testGet()
    {
        $this->assertEquals(null, $this->item->get());
    }

    public function testIsHit()
    {
        $this->assertFalse($this->item->isHit());
    }

    public function testSetHit()
    {
        $this->item->setHit(true);
        $this->assertTrue($this->item->isHit());
        $this->item->setHit(false);
        $this->assertFalse($this->item->isHit());
    }

    public function testSet()
    {
        $this->item->set('some value');
        $this->assertEquals('some value', $this->item->get());
    }

    public function testExpiresAtInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException',
                'Invalid expiration date. It can be null or implement DateTimeInterface.');
        $this->item->expiresAt(10);
    }

    public function testExpiresAt()
    {
        $time = time() + 3600;
        $expire = new \DateTime();
        $this->item->expiresAt($expire->setTimestamp($time));
        $this->assertEquals($time, $this->item->getExpiration()->getTimestamp());
    }

    public function testExpiresAtSetNull()
    {
        $this->item->expiresAt(null);
        $this->assertGreaterThanOrEqual(time() + 3500, $this->item->getExpiration()->getTimestamp());
        $this->assertLessThanOrEqual(time() + 3600, $this->item->getExpiration()->getTimestamp());
    }

    public function testExpiresAfterInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException',
                'Invalid time. It can be integer, null or instance of DateInterval.');
        $this->item->expiresAfter('invalid');
    }

    public function testExpiresAfterDateInterval()
    {
        $dateInterval = new \DateInterval('PT2S'); // 2 seconds
        $this->item->expiresAfter($dateInterval);
        $this->assertGreaterThanOrEqual(time(), $this->item->getExpiration()->getTimestamp());
        sleep(3);
        $this->assertLessThan(time(), $this->item->getExpiration()->getTimestamp());
    }

    public function testExpiresAfterSetInt()
    {
        $this->item->expiresAfter(2);
        $this->assertGreaterThanOrEqual(time(), $this->item->getExpiration()->getTimestamp());
        sleep(3);
        $this->assertLessThan(time(), $this->item->getExpiration()->getTimestamp());
    }

    public function testExpiresAfterSetNull()
    {
        $item = new CacheItem('my', 2);
        $item->expiresAfter(null);
        $this->assertGreaterThan(time(), $item->getExpiration()->getTimestamp());
        sleep(3);
        $this->assertLessThan(time(), $item->getExpiration()->getTimestamp());
    }

    public function testGetExpire()
    {
        $time = time() + 3600;
        $expire = new \DateTime();

        $this->item->expiresAt($expire->setTimestamp($time));
        $this->assertEquals($time, $this->item->getExpiration()->getTimestamp());
    }
}
