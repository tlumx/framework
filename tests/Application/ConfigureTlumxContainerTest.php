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

use Tlumx\Application\ConfigureTlumxContainer;
use Tlumx\ServiceContainer\ServiceContainer;
use Psr\Container\ContainerInterface;

class ConfigureTlumxContainerTest extends \PHPUnit\Framework\TestCase
{
    protected $config = [];

    private $container;

    private $configure;

    protected function setUp()
    {
        $this->container = new ServiceContainer();
        $this->configure = new ConfigureTlumxContainer();
        $this->config = [
            'service_container' => [
                'services' => [
                    'service1' => new \stdClass,
                    'service2' => 'service2-value'
                ],
                'factories' => [
                    'service-factory1' => 'ClassFactory1',
                    'service-factory2' => function (ContainerInterface $c) {
                        $obj = new stdClass;
                        $obj->property = 'value';
                        return $obj;
                    }
                ],
                'definitions' => [
                    'service-deff1' => [
                        'class' => 'SomeClass',
                        'args' => [
                            'aa' => 'a_val', 'bb' => 10, 'a' => ['ref' => 'A'], 'c' => ['ref' => 'this']
                        ],
                        'calls' => [
                            'setContainer' => [ ['ref' => 'this'] ]
                        ]
                    ],
                    'service-deff2' => [
                       'class' => 'SomeClass2'
                    ]
                ],
                'aliases' => [
                    'service-alias1' => 'service1',
                    'service-alias2' => 'service-factory2'
                ],
                'shared' => [
                    'service1' => true,
                    'service-factory1' => false,
                    'service-deff2' => false
                ]
            ]
        ];
    }

    public function testImplements()
    {
        $this->assertInstanceOf(\Tlumx\Application\ConfigureContainerInterface::class, $this->configure);
    }

    public function testConfigureContainer()
    {
        $c = $this->container;
        $this->configure->configureContainer($c, $this->config);

        $this->assertTrue($c->has('service1'));
        $this->assertTrue($c->has('service2'));
        $this->assertTrue($c->has('service-factory1'));
        $this->assertTrue($c->has('service-factory2'));
        $this->assertTrue($c->has('service-deff1'));
        $this->assertTrue($c->has('service-deff2'));
        $this->assertTrue($c->has('service-alias1'));
        $this->assertTrue($c->has('service-alias2'));

        $this->assertTrue($c->hasAlias('service-alias1'));
        $this->assertTrue($c->hasAlias('service-alias2'));
        $this->assertEquals('service1', $c->getServiceIdFromAlias('service-alias1'));
        $this->assertEquals('service-factory2', $c->getServiceIdFromAlias('service-alias2'));

        $reflectionC = new \ReflectionClass('\Tlumx\ServiceContainer\ServiceContainer');
        $reflection_property = $reflectionC->getProperty('keys');
        $reflection_property->setAccessible(true);
        $this->assertEquals([
            'service1' => true,
            'service2' => true,
            'service-factory1' => false,
            'service-factory2' => true,
            'service-deff1' => true,
            'service-deff2' => false
        ], $reflection_property->getValue($c));
    }

    public function testNotTlumxServiceContainer()
    {
        $tlumxContainerProphecy = $this->prophesize(\Tlumx\ServiceContainer\ServiceContainer::class);
        $this->configure->configureContainer($tlumxContainerProphecy->reveal(), $this->config);
        $tlumxContainerProphecy->set('service2', 'service2-value')->shouldBeCalled();

        $fakeContainerProphecy = $this->prophesize(\Tlumx\Tests\Application\Fixtures\TlumxContainerFake::class);
        $this->configure->configureContainer($fakeContainerProphecy->reveal(), $this->config);
        $fakeContainerProphecy->set('service2', 'service2-value')->shouldNotBeCalled();
    }
}
