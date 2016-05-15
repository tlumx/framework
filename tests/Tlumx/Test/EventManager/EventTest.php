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

use Tlumx\EventManager\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    
    public function testImplements()
    {
        $event = new Event('Test');
        $this->assertInstanceOf('Tlumx\EventManager\EventInterface', $event);
    }
    
    public function testEvent()
    {
        $event = new Event('Test');
        $this->assertEquals($event->getName(), 'Test');
        $this->assertEquals($event->getParams(), array());
        $this->assertNull($event->getParam('some'));
        $this->assertFalse($event->getParam('some',false));        
        $this->assertFalse($event->isStoppedPropagation());
        
        $event = new Event('Test2', array('foo'=>'bar', 'a'=>100));
        $this->assertEquals($event->getName(), 'Test2');
        $event->setName('NewTest2');
        $this->assertEquals($event->getName(), 'NewTest2');
        $this->assertEquals($event->getParams(), array('foo'=>'bar', 'a'=>100));
        $this->assertEquals($event->getParam('foo'), 'bar');
        $event->setParam('foo', 'baz');
        $this->assertEquals($event->getParam('foo'), 'baz');
        $event->stopPropagation();
        $this->assertTrue($event->isStoppedPropagation());        
    }    
}