<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\EventManager;

use Tlumx\EventManager\AbstractListener;

class ListenerForTesting extends AbstractListener
{
    public function addListeners()
    {
        $this->getEventManager()->addListener('event1', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count + 1);
        }, 1);

        $this->getEventManager()->addListener('event1', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count + 1);
        }, 2);

        $this->getEventManager()->addListener('event2', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count + 1);
        }, 5);

        $this->getEventManager()->addListener('event3', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count + 1);
        }, 1);
    }
}
