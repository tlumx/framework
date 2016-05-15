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

use Tlumx\Cache\ApcuCachePool;

class ApcuTest extends CacheTestCase
{
    protected $cacheDriver;
    
    protected $useRequestTime;
    
    public function setUp()
    {
        if (!extension_loaded('apcu')) {
            $this->markTestSkipped('The apcu extension must be loaded.');
        }

        if (!ini_get("apc.enable_cli")) {
            $this->markTestSkipped("The apcu cli extension is disabled.");
        }
            
        $this->useRequestTime = ini_get('apc.use_request_time');
        ini_set('apc.use_request_time', 0);
    }
    
    public function tearDown()
    {
        ini_set('apc.use_request_time', $this->useRequestTime);
        if (extension_loaded('apcu') || ini_get("apc.enable_cli")) {
            apc_clear_cache();
        }        
        unset($this->cacheDriver);
    }
    
    protected function getCacheDriver()
    {
        if(!$this->cacheDriver) {                        
            $this->cacheDriver = new ApcuCachePool([
                'prefix' => 'tlumxframework_tmp_cache'
            ]);
        }
        
        return $this->cacheDriver;
    }    
}