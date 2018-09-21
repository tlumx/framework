<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application;

use Tlumx\Application\ApplicationEvent;
use Psr\Container\ContainerInterface;
use Tlumx\EventManager\EventInterface;

class ApplicationEventTest extends \PHPUnit\Framework\TestCase
{
    public function getContainer()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        return $container;
    }

    public function testImplements()
    {
        $appEvent = new ApplicationEvent('');
        $this->assertInstanceOf(EventInterface::class, $appEvent);
    }

    public function testGetSetContainer()
    {
        $container = $this->getContainer();
        $appEvent = new ApplicationEvent('');

        $class = new \ReflectionClass($appEvent);
        $propertyContainer = $class->getProperty('container');
        $propertyContainer->setAccessible(true);
        $propertyParams = $class->getProperty('params');
        $propertyParams->setAccessible(true);

        $this->assertNull($propertyContainer->getValue($appEvent));
        $this->assertEquals([], $propertyParams->getValue($appEvent));
        $this->assertNull($appEvent->getContainer());

        $appEvent->setContainer($container);
        $this->assertEquals($container, $propertyContainer->getValue($appEvent));
        $this->assertEquals(['container' => $container], $propertyParams->getValue($appEvent));
        $this->assertEquals($container, $appEvent->getContainer());
    }
}
