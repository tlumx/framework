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

use Tlumx\EventManager\EventManager;
use Tlumx\EventManager\Event;
use Tlumx\Tests\EventManager\ListenerForTesting;

class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testListeners()
    {
        $eventManager = new EventManager();

        $listener1 = function ($e) {
            // some code ...
        };
        $listener2 = function ($e) {
            // some code ...
        };
        $listener3 = function ($e) {
            // some code ...
        };

        $this->assertTrue($eventManager->attach('event1', $listener1, 2));
        $this->assertTrue($eventManager->attach('event1', $listener2));
        $this->assertTrue($eventManager->attach('event2', $listener3, 1));

        $this->assertTrue($eventManager->attach('event1', [
            'Tlumx\Tests\EventManager\ListenerForTesting',
            'attachListener'
        ], 1));

        $listener5 = new ListenerForTesting();
        $this->assertTrue($eventManager->attach('event2', $listener5));

        $this->assertFalse($eventManager->attach('event3', 'no_callable'));

        $ref = new \ReflectionClass('Tlumx\EventManager\EventManager');
        $reflectionProperty = $ref->getProperty('listeners');
        $reflectionProperty->setAccessible(true);
        $listeners = $reflectionProperty->getValue($eventManager);

        $this->assertEquals(2, count($listeners));
        $this->assertEquals([
            'event1' => [
                0 => [$listener2],
                1 => [[
                    'Tlumx\Tests\EventManager\ListenerForTesting',
                    'attachListener'
                ]],
                2 => [$listener1]
            ],
            'event2' => [
                0 => [$listener5],
                1 => [$listener3]
            ]
        ], $listeners);

        $this->assertFalse($eventManager->detach('event3', ''));
        $this->assertFalse($eventManager->detach('event3', function () {
        }));
        $this->assertTrue($eventManager->detach('event1', $listener1));

        $listeners = $reflectionProperty->getValue($eventManager);
        $this->assertEquals([
            'event1' => [
                0 => [$listener2],
                1 => [[
                    'Tlumx\Tests\EventManager\ListenerForTesting',
                    'attachListener'
                ]]
            ],
            'event2' => [
                0 => [$listener5],
                1 => [$listener3]
            ]
        ], $listeners);
        $this->assertTrue($eventManager->detach('event1', [
            'Tlumx\Tests\EventManager\ListenerForTesting',
            'attachListener'
        ]));
        $listeners = $reflectionProperty->getValue($eventManager);
        $this->assertEquals([
            'event1' => [
                0 => [$listener2]
            ],
            'event2' => [
                0 => [$listener5],
                1 => [$listener3]
            ]
        ], $listeners);

        $eventManager->clearListeners('event1');
        $listeners = $reflectionProperty->getValue($eventManager);
        $this->assertEquals([
            'event2' => [
                0 => [$listener5],
                1 => [$listener3]
            ]
        ], $listeners);
        $eventManager->clearListeners('event2');
        $listeners = $reflectionProperty->getValue($eventManager);
        $this->assertEquals([], $listeners);
    }

    public function testEventManagerTrigger()
    {
        $eventManager = new EventManager();

        $event = new Event('event1');
        $event->setParams(['count' => 0]);

        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
        }, 2);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);

            // It's will be return the result from the last listener.
            return 'last';
        }, 2);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
        }, 1);
        $eventManager->attach('event2', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
        });

        $eventManager->attach('event1', [
            'Tlumx\Tests\EventManager\ListenerForTesting',
            'attachListener'
        ]);
        $listener = new ListenerForTesting();
        $this->assertTrue($eventManager->attach('event2', $listener));


        $result = $eventManager->trigger($event);
        $this->assertEquals(4, $event->getParam('count'));
        $this->assertEquals('last', $result);
    }

    public function testStopPropagation()
    {
        $eventManager = new EventManager();
        $event = new Event('event1');
        $event->setParams(['count' => 0]);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
            return 'res3';
        }, 3);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
            $e->stopPropagation(true);
            return 'res2';
        }, 2);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
            $e->stopPropagation(false);
            return 'res1';
        }, 1);
        $eventManager->attach('event1', function ($e) {
            $count = $e->getParam('count');
            $count++;
            $e->setParams(['count' => $count]);
            return 'res0';
        });

        $result = $eventManager->trigger($event);
        $this->assertEquals(true, $event->isPropagationStopped());
        $this->assertEquals(3, $event->getParam('count'));
        $this->assertEquals('res2', $result);
    }
}
