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

use Tlumx\Application\Bootstrapper;
use Tlumx\Application\ConfigureTlumxContainer;
use Tlumx\Application\DefaultContainerFactory;
use Tlumx\Application\ApplicationEvent as AppEvent;
use Tlumx\ServiceContainer\ServiceContainer;
use Psr\Container\ContainerInterface;
use Tlumx\Tests\Application\Fixtures\ABootstrapper;
use Tlumx\Tests\Application\Fixtures\BBootstrapper;
use Tlumx\Tests\Application\Fixtures\InvalidConfigBootstrapper;
use Tlumx\Tests\Application\Fixtures\InvalidServicesConfigBootstrapper;
use Tlumx\Application\Exception\InvalidBootstrapperClassException;

class BootstrapperTest extends \PHPUnit\Framework\TestCase
{
    protected $config = [];

    private $container;

    private $configure;

    protected function setUp()
    {
        $this->container = new ServiceContainer();
        $this->configure = new ConfigureTlumxContainer();
        $this->config = [
            'a' => 'value_a',
            'b' => 'value_b',
            'c' => [
                'c1' => 'value_c1',
                'c2' => 'value_c2',
                'c3' => [
                    'c3_1' => 'value_c3_1',
                    'c3_2' => 'value_c3_3',
                ]
            ]
        ];
        $factory = new DefaultContainerFactory();
        $this->container = $factory->create($this->config);
    }

    public function testImplements()
    {
        $bootstrapperA = new ABootstrapper($this->container, $this->configure);
        $bootstrapperB = new BBootstrapper($this->container, $this->configure);
        $this->assertInstanceOf(\Tlumx\Application\Bootstrapper::class, $bootstrapperA);
        $this->assertInstanceOf(\Tlumx\Application\Bootstrapper::class, $bootstrapperB);
    }

    public function testInit()
    {
        $bootstrapperA = new ABootstrapper($this->container, $this->configure);
        $this->assertFalse($this->container->has('init_service'));

        $bootstrapperB = new BBootstrapper($this->container, $this->configure);
        $this->assertTrue($this->container->has('init_service'));
        $this->assertEquals('init_service_value', $this->container->get('init_service'));
    }

    public function testEvents()
    {
        $bootstrapperA = new ABootstrapper($this->container, $this->configure);
        $em = $this->container->get('event_manager');

        $this->assertEquals(null, $em->trigger(AppEvent::EVENT_POST_BOOTSTRAP));
        $this->assertEquals(null, $em->trigger(AppEvent::EVENT_PRE_ROUTING));
        $this->assertEquals(null, $em->trigger(AppEvent::EVENT_POST_ROUTING));
        $this->assertEquals(null, $em->trigger(AppEvent::EVENT_PRE_DISPATCH));
        $this->assertEquals(null, $em->trigger(AppEvent::EVENT_POST_DISPATCH));

        $bootstrapperB = new BBootstrapper($this->container, $this->configure);
        $this->assertEquals('postBootstrap', $em->trigger(AppEvent::EVENT_POST_BOOTSTRAP));
        $this->assertEquals('preRouting', $em->trigger(AppEvent::EVENT_PRE_ROUTING));
        $this->assertEquals('postRouting', $em->trigger(AppEvent::EVENT_POST_ROUTING));
        $this->assertEquals('preDispatch', $em->trigger(AppEvent::EVENT_PRE_DISPATCH));
        $this->assertEquals('postDispatch', $em->trigger(AppEvent::EVENT_POST_DISPATCH));
    }

    public function testGetConfig()
    {
        $bootstrapperA = new ABootstrapper($this->container, $this->configure);
        $config = $this->container->get('config');

        $this->assertEquals($this->config, $config->getAll());

        $bootstrapperB = new BBootstrapper($this->container, $this->configure);
        $this->assertEquals([
            'a' => 'value_a',
            'b' => 'value_b',
            'c' => [
                'c1' => 'value_c1',
                'c2' => 'value_c2',
                'c3' => [
                    'c3_1' => 'value_c3_1',
                    'c3_2' => 'value_c3_3',
                    'c3_3' => 'value_c3_3_from_b',
                ]
            ],
            'd' => 'd_value'
        ], $config->getAll());
    }

    public function testInvalidGetConfig()
    {
        $this->expectException(InvalidBootstrapperClassException::class);
        $this->expectExceptionMessage(sprintf(
            "Method \"getConfig\" must return array of configuration, from Bootstrapper class: \"%s\".",
            InvalidConfigBootstrapper::class
        ));
        $bootstrapperB = new InvalidConfigBootstrapper($this->container, $this->configure);
    }

    public function testInvalidGetServiceConfig()
    {
        $this->expectException(InvalidBootstrapperClassException::class);
        $this->expectExceptionMessage(sprintf(
            "Method \"getServiceConfig\" must return array of services configuration," .
            "from Bootstrapper class: \"%s\".",
            InvalidServicesConfigBootstrapper::class
        ));
        $bootstrapperB = new InvalidServicesConfigBootstrapper($this->container, $this->configure);
    }

    public function testGetServiceConfig()
    {
        $bootstrapperA = new ABootstrapper($this->container, $this->configure);
        $this->assertFalse($this->container->has('service1'));

        $bootstrapperB = new BBootstrapper($this->container, $this->configure);
        $this->assertTrue($this->container->has('service1'));
        $this->assertEquals('value1', $this->container->get('service1'));
    }
}
