<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Cache;

/**
 * Memcached caching.
 */
class MemcachedCachePool extends AbstractCacheItemPool
{
    /**
     * @var \Memcached
     */
    protected $memcached;

    /**
     * Constructor
     *
     * @param \Memcached $memcached
     * @param array $options
     */
    public function __construct(\Memcached $memcached, array $options = [])
    {
        $this->memcached = $memcached;

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function setDataToStorage($key, $value, $ttl)
    {
        $ttl = (int) $ttl;

        return $this->memcached->set($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFromStorage($key)
    {
        return $this->memcached->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function getArrayDataFromStorage(array $keys)
    {
        $result = $this->memcached->getMulti($keys);
        if ($result === false) {
            return [];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function isHavDataInStorage($key)
    {
        return $this->memcached->get($key) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteDataFromStorage($key)
    {
        return $this->memcached->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteArrayDataFromStorage(array $keys)
    {
        $deleted = true;

        foreach ($keys as $key) {
            if ($this->memcached->delete($key) === false) {
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
        return $this->memcached->flush();
    }
}
