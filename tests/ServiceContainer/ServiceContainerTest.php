<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\ServiceContainer;

use Tlumx\ServiceContainer\ServiceContainer;
use Tlumx\ServiceContainer\Exception\ContainerException;
use Tlumx\ServiceContainer\Exception\NotFoundException;

class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testImplements()
    {
        $this->assertInstanceOf(
            'Psr\Container\ContainerInterface',
            new ServiceContainer()
        );
        $this->assertInstanceOf(
            'Psr\Container\NotFoundExceptionInterface',
            new NotFoundException
        );
        $this->assertInstanceOf(
            'Psr\Container\ContainerExceptionInterface',
            new ContainerException
        );
    }

    public function testGetSetIssetRemove()
    {
        $c = new ServiceContainer();
        $this->assertFalse($c->has('some1'));
        $c->set('some1', 'value1');
        $this->assertTrue($c->has('some1'));
        $this->assertEquals('value1', $c->get('some1'));
        $c->remove('some1');
        $this->assertFalse($c->has('some1'));
    }

    public function testSetValuesByConstructor()
    {
        $c = new ServiceContainer([
            'service1' => 'value1',
            'service2' => 'value2',
            'service3' => 'value3'
        ]);
        $this->assertTrue($c->has('service1'));
        $this->assertTrue($c->has('service2'));
        $this->assertTrue($c->has('service3'));
        $this->assertEquals('value1', $c->get('service1'));
        $this->assertEquals('value2', $c->get('service2'));
        $this->assertEquals('value3', $c->get('service3'));
    }

    public function testGetIfNotFound()
    {
        $index = 'some';
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\NotFoundException',
            sprintf(
                'The service "%s" is not found',
                $index
            )
        );

        $c = new ServiceContainer();
        $c->get($index);
    }

    public function testRegisterClosure()
    {
        $c = new ServiceContainer();
        $c->register('service1', function () {
            return rand();
        });
        $c->register('service2', function () {
            return rand();
        }, false);

        $this->assertSame($c->get('service1'), $c->get('service1'));
        $this->assertNotSame($c->get('service2'), $c->get('service2'));
    }

    public function testInvalidRegisterFactoryNotCallable()
    {
        $c = new ServiceContainer();
        $c->register('service1', 'Tlumx\Tests\ServiceContainer\A');
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created: "Service factory may be callable or string '
                . '(that can be resolving to an invokable class '
                . 'or to a FactoryInterface instance).'
        );

        $c->get('service1');
    }

    public function testInvalidRegisterFactoryNotInvokeClass()
    {
        $c = new ServiceContainer();
        $c->register('service1', 'Tlumx\Tests\ServiceContainer\NotInvokable');
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created: "There were incorrectly '
                . 'transmitted data when registering the service".'
        );

        $c->get('service1');
    }

    public function testInvalidRegisterFactoryNotStringClass()
    {
        $c = new ServiceContainer();
        $c->register('service1', 'not_class');
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created: "There were incorrectly '
                . 'transmitted data when registering the service".'
        );

        $c->get('service1');
    }

    public function testRegisterFactoryFromString()
    {
        $c = new ServiceContainer();
        $c->register('service1', 'Tlumx\Tests\ServiceContainer\MyFactory');
        $c->register('service2', 'Tlumx\Tests\ServiceContainer\MyFactory', false);

        $this->assertSame($c->get('service1'), $c->get('service1'));
        $this->assertNotSame($c->get('service2'), $c->get('service2'));

        $c->register('service3', 'Tlumx\Tests\ServiceContainer\MyFactory2');
        $c->register('service4', 'Tlumx\Tests\ServiceContainer\MyFactory2', false);

        $this->assertEquals(1, $c->get('service3'));
        $this->assertEquals(1, $c->get('service4'));
        $c->set('a', 2);
        $this->assertEquals(1, $c->get('service3'));
        $this->assertEquals(3, $c->get('service4'));
    }

    public function testRegisterImmutableService()
    {
        $index = 'some';
        $c = new ServiceContainer();
        $c->register($index, function () {
        });
        $val = $c->get($index);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $index
            )
        );
        $c->register($index, function () {
        });
    }

    public function testSetIfImmutable()
    {
        $c = new ServiceContainer();
        $c->register('some', function () {
        });
        $val = $c->get('some');
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                'some'
            )
        );

        $c->set('some', 'value');
    }

    public function testRegisterFromDefinitionImmutableService()
    {
        $index = 'some';
        $c = new ServiceContainer();
        $c->register($index, function () {
        });
        $val = $c->get($index);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $index
            )
        );
        $c->registerDefinition($index, []);
    }

    public function testRegisterFromDefinitionErrorClassNotDefined()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', []);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' option "class" is not exists in definition array.'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorClassIsNotExists()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'SomeInvalidClass'
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' Class "SomeInvalidClass" is not exists.'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorClassIsNotInstantiable()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'Tlumx\Tests\ServiceContainer\NotInstantiable'
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' Unable to create instance of class "Tlumx\Tests\ServiceContainer\NotInstantiable".'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorConstructorArgsIsNotExists()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'Tlumx\Tests\ServiceContainer\A'
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' option "args" is not exists in definition array.'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorCanNotCallMethod()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'Tlumx\Tests\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'invalidMethod' => []
            ]
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' can not call method "invalidMethod" from class: "Tlumx\Tests\ServiceContainer\A"'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorInvalidArgs()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'Tlumx\Tests\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => []
            ]
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' unable resolve parameter.'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinitionErrorArgsNotInContainer()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('service1', [
            'class' => 'Tlumx\Tests\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [['ref' => 'invalid-service']]
            ]
        ]);
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Service could not be created from definition:'
                . ' unable resolve parameter.'
        );
        $c->get('service1');
    }

    public function testRegisterFromDefinition()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('B', [
            'class' => 'Tlumx\Tests\ServiceContainer\B',
            'args' => ['a' => ['ref' => 'A']]
        ]);
        $c->registerDefinition('A', [
            'class' => 'Tlumx\Tests\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [ ['ref' => 'this'] ]
            ]
        ], false);

        $this->assertInstanceOf('Tlumx\Tests\ServiceContainer\B', $c->get('B'));

        $b = $c->get('B');
        $a = $b->getA();

        $this->assertInstanceOf('Tlumx\Tests\ServiceContainer\A', $a);
        $this->assertEquals('some value', $a->getSome());
        $this->assertEquals($c, $a->getContainer());

        $this->assertSame($b, $c->get('B'));
        $this->assertNotSame($a, $c->get('A'));
    }

    public function testSetAliasAlisaNameAndServiceNameEquals()
    {
        $c = new ServiceContainer();
        $this->setExpectedException(
            'Tlumx\ServiceContainer\Exception\ContainerException',
            'Alias and service names can not be equals'
        );
        $c->setAlias('some', 'some');
    }

    public function testGetSetIssetUnsetAliases()
    {
        $c = new ServiceContainer();
        $this->assertEquals([], $c->getAliases());
        $c->setAlias('alias1', 'service1');
        $c->setAlias('alias2', 'service1');
        $this->assertEquals('service1', $c->getServiceIdFromAlias('alias1'));
        $this->assertEquals('service1', $c->getServiceIdFromAlias('alias2'));
        $this->assertEquals(null, $c->getServiceIdFromAlias('alias3'));
        $this->assertEquals(false, $c->getServiceIdFromAlias('alias3', false));
        $this->assertEquals(['alias1' => 'service1', 'alias2' => 'service1'], $c->getAliases());
        $this->assertTrue($c->hasAlias('alias1'));
        $this->assertTrue($c->hasAlias('alias2'));
        $this->assertFalse($c->hasAlias('alias3'));
        $c->removeAlias('alias1');
        $this->assertEquals(['alias2' => 'service1'], $c->getAliases());
    }

    public function testUseAlias()
    {
        $c = new ServiceContainer();
        $c->registerDefinition('B', [
            'class' => 'Tlumx\Tests\ServiceContainer\B',
            'args' => ['a' => ['ref' => 'A']]
        ]);
        $c->registerDefinition('A', [
            'class' => 'Tlumx\Tests\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [ ['ref' => 'this'] ]
            ]
        ]);
        $c->register('C', function () {
        });
        $c->set('a', 'aaa');
        $c->setAlias('a-alias', 'a');
        $c->setAlias('B-alias', 'B');
        $c->setAlias('c_alias', 'C');

        $this->assertTrue($c->hasAlias('a-alias'));
        $this->assertTrue($c->hasAlias('B-alias'));
        $this->assertTrue($c->hasAlias('c_alias'));
        $this->assertEquals($c->get('a'), $c->get('a-alias'));
        $this->assertNotSame($c->get('A'), $c->get('B-alias'));
        $this->assertSame($c->get('B'), $c->get('B-alias'));

        // if service name overide alias name, alias remove
        $c->set('a-alias', 'some value');
        $this->assertNotEquals($c->get('a'), $c->get('a-alias'));
        $this->assertFalse($c->hasAlias('a-alias'));
        $c->registerDefinition('B-alias', [
            'class' => 'Tlumx\Tests\ServiceContainer\B',
            'args' => ['a' => ['ref' => 'A']]
        ]);
        $this->assertFalse($c->hasAlias('B-alias'));
        $c->register('c_alias', function () {
        });
        $this->assertFalse($c->hasAlias('c_alias'));
    }
}
