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

use Cache\IntegrationTests\CachePoolTest;
use Tlumx\Cache\XCacheCachePool;

class XCachePoolIntegrationTest extends CachePoolTest
{
    public function createCachePool()
    {
        if (!extension_loaded('xcache')) {
            $this->markTestSkipped('The xcache extension must be loaded.');
        }
        
        return new XCacheCachePool([
            'prefix' => 'tlumxframework_tmp_cache'
        ]);        
    }
}