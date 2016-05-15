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
 * Abstract event listener class
 */
abstract class AbstractListener implements  ListenerInterface
{
    /**
     * @var \Tlumx\EventManager\EventManager $eventManager
     */
    protected $eventManager;

    /**
     * Set EventManager
     *
     * @param \Tlumx\EventManager\EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Get EventManager
     *
     * @return \Tlumx\EventManager\EventManager
     * @throws \RuntimeException
     */
    public function getEventManager()
    {
        if(!$this->eventManager instanceof EventManager) {
            throw new \RuntimeException('EventManager is not set.');
        }
        
        return $this->eventManager;
    }

    /**
     * Add listener to EventManager
     */
    abstract function addListeners();
}