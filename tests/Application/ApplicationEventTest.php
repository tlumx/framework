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
use Tlumx\Application\Application;
use Tlumx\EventManager\Event;
use Tlumx\EventManager\EventInterface;

class ApplicationEventTest extends \PHPUnit\Framework\TestCase
{
    public function getApplication()
    {
        $app = $this->prophesize(Application::class)->reveal();
        return $app;
    }

    public function testImplements()
    {
        $appEvent = new ApplicationEvent('');
        $this->assertInstanceOf(EventInterface::class, $appEvent);
    }

    public function testGetSetApplication()
    {
        $app = $this->getApplication();
        $appEvent = new ApplicationEvent('');

        $class = new \ReflectionClass($appEvent);
        $propertyApplication = $class->getProperty('application');
        $propertyApplication->setAccessible(true);
        $propertyParams = $class->getProperty('params');
        $propertyParams->setAccessible(true);

        $this->assertNull($propertyApplication->getValue($appEvent));
        $this->assertEquals([], $propertyParams->getValue($appEvent));
        $this->assertNull($appEvent->getApplication());

        $appEvent->setApplication($app);
        $this->assertEquals($app, $propertyApplication->getValue($appEvent));
        $this->assertEquals(['application' => $app], $propertyParams->getValue($appEvent));
        $this->assertEquals($app, $appEvent->getApplication());
    }
}
