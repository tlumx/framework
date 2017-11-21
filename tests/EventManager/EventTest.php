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

use Tlumx\EventManager\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{

    public function testImplements()
    {
        $event = new Event('Test');
        $this->assertInstanceOf('Tlumx\EventManager\EventInterface', $event);
    }

    public function testName()
    {
        $event = new Event('Test');
        $this->assertTrue('Test' === $event->getName());
        $name = 'some';
        $event->setName($name);
        $this->assertTrue($name === $event->getName());
    }

    public function testTarget()
    {
        $event = new Event('Test');
        $this->assertTrue(null === $event->getTarget());
        $event->setTarget($this);
        $this->assertTrue($this === $event->getTarget());
        $event2 = new Event('Test', $this);
        $this->assertTrue($this === $event2->getTarget());
    }

    public function testParams()
    {
        $event = new Event('Test');
        $this->assertTrue([] === $event->getParams());
        $this->assertTrue(null === $event->getParam('param1'));
        $params = ['param1' => 'val1', 'param2' => 'val2'];
        $event->setParams($params);
        $this->assertTrue($params === $event->getParams());
        $this->assertTrue('val1' === $event->getParam('param1'));
        $this->assertTrue('val2' === $event->getParam('param2'));
        $this->assertTrue(null === $event->getParam('param3'));

        $params = ['param1' => 'val1', 'param2' => 'val2','param3' => 'val3'];
        $event2 = new Event('Test', null, $params);
        $this->assertTrue($params === $event2->getParams());
        $this->assertTrue('val1' === $event2->getParam('param1'));
        $this->assertTrue('val2' === $event2->getParam('param2'));
        $this->assertTrue('val3' === $event2->getParam('param3'));
        $this->assertTrue(null === $event2->getParam('param4'));
    }

    public function testStopPropagation()
    {
        $event = new Event('Test');
        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation(true);
        $this->assertTrue($event->isPropagationStopped());
        $event->stopPropagation(false);
        $this->assertFalse($event->isPropagationStopped());
    }
}
