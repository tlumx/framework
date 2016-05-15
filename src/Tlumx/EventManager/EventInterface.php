<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\EventManager;

/**
 * Event interface
 */
interface EventInterface
{
    /**
     * Get event name
     *
     * @return string
     */
    public function getName();

    /**
     * Set event name
     *
     * @param string $name
     * @return \Tlumx\EventManager\Event
     */
    public function setName($name);

    /**
     * Get event params
     *
     * @return array
     */
    public function getParams();

    /**
     * Set event params
     *
     * @param array $params
     * @return \Tlumx\EventManager\Event
     */
    public function setParams(array $params);

    /**
     * Get event param by name
     *
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null);

    /**
     * Set event param
     *
     * @param mixed $name
     * @param mixed $value
     * @return \Tlumx\EventManager\Event
     */
    public function setParam($name, $value);

    /**
     * Whether or not to stop propagation
     *
     * @return bool
     */
    public function isStoppedPropagation();

    /**
     * Stop propagation
     */
    public function stopPropagation();
}