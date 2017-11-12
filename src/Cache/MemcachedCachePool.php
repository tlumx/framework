<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
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
        $value = $this->memcached->get($key);
        if ($value === false) {
            return false;
        }

        return [$value];
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
        if ($this->memcached->get($key) !== false) {
            return $this->memcached->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteArrayDataFromStorage(array $keys)
    {
        $deleted = true;

        foreach ($keys as $key) {
            if ($this->memcached->delete($key) === false) {
                if ($this->memcached->get($key) !== false) {
                    $deleted = false;
                }
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
