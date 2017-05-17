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

use Tlumx\Cache\MemcachedCachePool;

class MemcachedTest extends CacheTestCase
{
    protected $cacheDriver;

    protected $memcached;

    public function setUp()
    {
        if (!extension_loaded("memcached")) {
            $this->markTestSkipped("Memcached not installed. Skipping.");
        }
    }

    public function tearDown()
    {
        if ($this->memcached) {
            $this->memcached->flush();
            $this->memcached = null;
        }
        $this->cacheDriver = null;
    }

    protected function getCacheDriver()
    {
        if (!$this->cacheDriver) {
            $this->memcached = new \Memcached();
            $this->memcached->addServer('localhost', 11211);
            $this->cacheDriver = new MemcachedCachePool($this->memcached, [
                'prefix' => 'tlumxframework_tmp_cache'
            ]);
        }

        return $this->cacheDriver;
    }
}
