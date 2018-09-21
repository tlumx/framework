<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Application;

/**
 * Provides simplify access to configuration data.
 */
class Config implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * The configuration data.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->items = $config;
    }

    /**
     * Whether an key exists in this config.
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Retrieve config item by the key.
     *
     * @param mixed $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->items[$key] : $default;
    }

    /**
     * Retrieve all config items.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * Set config item by the key.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Remove config item by the key.
     *
     * @param mixed $key
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Remove all config items.
     */
    public function removeAll()
    {
        $this->items = [];
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Whether an offset exists.
     *
     * This method is executed when using isset() or empty().
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     *
     * Returns the value at specified offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Assign a value to the specified offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset an offset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Retrieve an external iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Merge this config data with given array.
     *
     * @var array $config
     */
    public function mergeWith(array $config)
    {
        $this->items = self::merge($this->items, $config);
    }

    /**
     * Merge given array with this config data and replacing this data with the result.
     *
     * @var array $config
     */
    public function mergeTo(array $config)
    {
        $this->items = self::merge($config, $this->items);
    }

    /**
     * Merge two arrays.
     *
     * If two array has integer key - the value from the second array - appended to the first.
     * If two array has array value - merged together.
     * Else - the value will be overwrites by the value of the second array.
     *
     * @var array $a
     * @var array $b
     * @return array
     */
    public static function merge(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) || array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = self::merge($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}
