<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Cache;

/**
 * XCache caching is a wrapper for the XCache extension.
 */
class XCacheCachePool extends AbstractCacheItemPool
{
    /**
     * Constructor
     *
     * @param array $options
     * @throws Exception\CacheException
     */
    public function __construct(array $options = [])
    {
        if(!extension_loaded('xcache')) {
            throw new Exception\CacheException('XCache extension must be loaded.');
        }
        
        if(!ini_get('xcache.cacher') || (ini_get('xcache.var_size') <=0)) {
            throw new Exception\CacheException('XCache extension is disabled.');
        }
        
        if (ini_get('xcache.admin.enable_auth')) {
            throw new Exception\CacheException('Is required set "xcache.admin.enable_auth" to "Off" in php.ini.');
        }
        
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function setDataToStorage($key, $value, $ttl)
    {
        $ttl = (int) $ttl;
        
        return xcache_set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFromStorage($key)
    {
        if (xcache_isset($key)) {
            return xcache_get($key);
        }
        
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getArrayDataFromStorage(array $keys)
    {
        $result = [];
        
        foreach ($keys as $key) {
            if(xcache_isset($key)) {
                $result[$key] = xcache_get($key);
            }
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isHavDataInStorage($key)
    {
        return xcache_isset($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteDataFromStorage($key)
    {
        return xcache_unset($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteArrayDataFromStorage(array $keys)
    {
        $deleted = true;
        
        foreach ($keys as $key) {
            if(xcache_unset($key) === false) {
                $deleted = false;
            }
        }
        
        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    protected function clearAllDataFromStorage()
    {
        if(xcache_clear_cache(XC_TYPE_VAR) === false) {
            return false;
        }
        
        return true;
    }
}