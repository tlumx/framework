<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\EventManager;

use Tlumx\EventManager\AbstractListener;

class ListenerForTesting extends AbstractListener
{
    public function addListeners()
    {
        $this->getEventManager()->addListener('event1', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 1);
        
        $this->getEventManager()->addListener('event1', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 2);        
            
        $this->getEventManager()->addListener('event2', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 5);

        $this->getEventManager()->addListener('event3', function ($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 1);
    }    
}