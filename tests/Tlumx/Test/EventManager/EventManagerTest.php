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

use Tlumx\EventManager\EventManager;
use Tlumx\EventManager\Event;

class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    
    
    public function testListeners()
    {
        $eventManager = new EventManager();
        $eventManager->addListener('event1', function($e) {
            // some code ...
        }, 2);
        $eventManager->addListener('event1', function($e) {
            // some code ...
        }, 2);
        $eventManager->addListener('event1', function($e) {
            // some code ...
        }, 1);
        $eventManager->addListener('event2', function($e) {
            // some code ...
        });
        
        $this->assertTrue($eventManager->hasListeners('event1'));            
        $this->assertTrue($eventManager->hasListeners('event2'));
        $this->assertFalse($eventManager->hasListeners('event3'));
        
        include_once  __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'ListenerForTesting.php';
        $listenerObj = new ListenerForTesting(); 
        $eventManager->addListenerObject($listenerObj);
        
        $this->assertTrue($eventManager->hasListeners('event3'));
        
        $listeners = $eventManager->getListeners('event1');
        
        // priority levels
        $this->assertEquals(count($listeners), 2);
        
        $eventManager->removeListeners('event1');
        $this->assertFalse($eventManager->hasListeners('event1'));        
    }
    
    public function testEventManagerTrigger()
    {
        $eventManager = new EventManager();
        
        $event = new Event('event1');
        $event->setParam('count', 0);
        
        $eventManager->addListener('event1', function($e){
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 2);
        $eventManager->addListener('event1', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);            
        }, 2);
        $eventManager->addListener('event1', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 1);
        $eventManager->addListener('event2', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        });
                
        include_once  __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'ListenerForTesting.php';                
        $listenerObj = new ListenerForTesting();
        $eventManager->addListenerObject($listenerObj);
        
        $eventManager->trigger($event);        
        $this->assertEquals($event->getParam('count'), 5);                
    }

    public function testStopPropagation()
    {
        $eventManager = new EventManager();
        $event = new Event('event1');
        $event->setParam('count', 0);
        $eventManager->addListener('event1', function($e){
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 2);
        $eventManager->addListener('event1', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 2);
        $eventManager->addListener('event1', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
        }, 1);
        $eventManager->addListener('event1', function($e) {
            $count = $e->getParam('count', 0);
            $e->setParam('count', $count+1);
            $e->stopPropagation();
        });
        
        $eventManager->trigger($event);
        $this->assertEquals($event->getParam('count'), 2);
    }
    
}