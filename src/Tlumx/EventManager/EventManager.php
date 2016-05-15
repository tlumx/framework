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
 * EventManager class
 */
class EventManager
{
    /**
     * @var array
     */
    protected $listeners = array();

    /**
     * Add listener to Event Manager
     *
     * @param string $eventName
     * @param \Closure $target
     * @param int $priority
     */
    public function addListener($eventName, \Closure $target, $priority = 1)
    {
    	$this->listeners[$eventName][$priority][] = $target;
    }

    /**
     * Add listener object to Event Manager
     *
     * @param \Tlumx\EventManager\ListenerInterface $listener
     */
    public function addListenerObject(ListenerInterface $listener)
    {
    	$listener->setEventManager($this);
    	$listener->addListeners();
    }

    /**
     * Remove all listener by event name
     *
     * @param string $eventName
     */
    public function removeListeners($eventName)
    {
    	unset($this->listeners[$eventName]);
    }

    /**
     * Get all listener by event name
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
    	if(!$this->hasListeners($eventName)) {
            return array();
    	}
        
    	ksort($this->listeners[$eventName]);
    	return 	array_values($this->listeners[$eventName]);
    }

    /**
     * Is isset listeners be event name
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
    	return isset($this->listeners[$eventName]);
    }

    /**
     * Trigger
     *
     * @param string|\Tlumx\EventManager\Event $event
     * @param array $params
     * @return bool
     */
    public function trigger($event, array $params = array())
    {
    	if (!$event instanceof Event) {
            $event = new Event($event, $params);
    	}
    	
    	foreach($this->getListeners($event->getName()) as $listeners) {
            foreach ($listeners as $listener) {
                $result = call_user_func($listener, $event);
                if (false === $result) {
                    return false;
                }
                if ($event->isStoppedPropagation()) {
                    break 2;
                }
            }
    	}
    	
    	return true;
    }
}