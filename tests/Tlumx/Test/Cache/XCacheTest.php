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

use Tlumx\Cache\XCacheCachePool;

class XCacheTest extends CacheTestCase
{
    protected $cacheDriver;
    
    protected $useRequestTime;
    
    public function setUp()
    {
        if(!extension_loaded('xcache')) {
            $this->markTestSkipped('The xcache extension must be loaded.');
        }
    }
    
    public function tearDown()
    {
        if(extension_loaded('xcache') && !ini_get('xcache.admin.enable_auth')) {
            xcache_clear_cache(XC_TYPE_VAR);
        }
        
        $this->cacheDriver = null;
    }
    
    protected function getCacheDriver()
    {
        if(!$this->cacheDriver) {                        
            $this->cacheDriver = new XCacheCachePool([
                'prefix' => 'tlumxframework_tmp_cache'
            ]);
        }
        
        return $this->cacheDriver;
    }    
}