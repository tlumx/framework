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
 * Event listener interface
 */
interface ListenerInterface
{
    /**
     * Set EventManager
     *
     * @param \Tlumx\EventManager\EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager);

    /**
     * Get EventManager
     *
     * @return \Tlumx\EventManager\EventManager
     * @throws \RuntimeException
     */
    public function getEventManager();

    /**
     * Add listener to EventManager
     */
    public function addListeners();
}
