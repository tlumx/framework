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
 * Event class
 */
class Event implements EventInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * Construct
     *
     * @param string $name
     * @param array $params
     */
    public function __construct($name, array $params = array())
    {
        $this->setName($name);
        $this->setParams($params);
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set event name
     *
     * @param string $name
     * @return \Tlumx\EventManager\Event
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Get event params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set event params
     *
     * @param array $params
     * @return \Tlumx\EventManager\Event
     */
    public function setParams(array $params)
    {	
        $this->params = $params;
        return $this;
    }

    /**
     * Get event param by name
     *
     * @param mixed $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return (isset($this->params[$name]) ? ($this->params[$name]) : $default);
    }

    /**
     * Set event param
     *
     * @param mixed $name
     * @param mixed $value
     * @return \Tlumx\EventManager\Event
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Whether or not to stop propagation
     *
     * @return bool
     */
    public function isStoppedPropagation()
    {
        return $this->propagationStopped;
    }

    /**
     * Stop propagation
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }
}