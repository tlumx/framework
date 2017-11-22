<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\EventManager;

/**
 * Event class.
 *
 * This is implementation of EventInterface
 * (the proposed Psr\EventManager\EventInterface).
 */
class Event implements EventInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var null|string|object
     */
    protected $target;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * Constructor
     *
     * @param string $name
     * @param string|object|null $target
     * @param array $params
     */
    public function __construct($name, $target = null, array $params = [])
    {
        $this->setName($name);
        $this->setTarget($target);
        $this->setParams($params);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * {@inheritDoc}
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * {@inheritDoc}
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * {@inheritDoc}
     */
    public function getParam($name)
    {
        return (isset($this->params[(string) $name]) ? ($this->params[(string) $name]) : null);
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
     * {@inheritDoc}
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * {@inheritDoc}
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation($flag)
    {
        $this->propagationStopped = (bool) $flag;
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }
}
