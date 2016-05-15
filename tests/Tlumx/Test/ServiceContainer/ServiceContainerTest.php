<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\ServiceContainer;

use Tlumx\ServiceContainer\ServiceContainer;
use Tlumx\ServiceContainer\Exception\ContainerException;
use Tlumx\ServiceContainer\Exception\NotFoundException;

class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testImplements()
    {
        $this->assertInstanceOf(
                'Interop\Container\ContainerInterface',
                new ServiceContainer()
        );
        $this->assertInstanceOf(
                'Interop\Container\Exception\NotFoundException',
                new NotFoundException
        );
        $this->assertInstanceOf(
                'Interop\Container\Exception\ContainerException',
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
        $this->assertTrue($c->has('service1'));
        $this->assertTrue($c->has('service1'));
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
        $c->get('some');
    }
              
    public function testRegisterSomeValue()
    {
        $c = new ServiceContainer();
        $c->register('some', 'value');
        $this->assertEquals('value', $c->get('some'));
    }
    
    public function testRegisterClosure()
    {
        $c = new ServiceContainer();
        $c->register('service1', function() {
            return rand();
        });
        $c->register('service2', function() {
            return rand();
        }, false);        
        
        $this->assertSame($c->get('service1'), $c->get('service1'));
        $this->assertNotSame($c->get('service2'), $c->get('service2'));
    }
    
    public function testRegisterImmutableService()
    {
        $index = 'some';
        $c = new ServiceContainer();
        $c->register('some', function(){});
        $some = $c->get('some');
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                sprintf(
                    'A service by the name "%s" already exists and cannot be overridden',
                    $index
                )
        );    
        $c->register('some', []);
    }    
        
    public function testSetIfImmutable()
    {
        $c = new ServiceContainer();
        $c->register('some', function(){});
        $some = $c->get('some');
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                sprintf(
                    'A service by the name "%s" already exists and cannot be overridden',
                    'some'
                )
        );
        
        $c->set('some', 'value');
    }     
    
    public function testRegisterFromDefinitionErrorClassNotDefined()
    {
        $c = new ServiceContainer();
        $c->register('service1', []);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException'
        );
        $c->get('service1');
    }
    
    public function testRegisterFromDefinitionErrorClassIsNotExists()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'SomeInvalidClass'
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Class "SomeInvalidClass" is not exists'
        );
        $c->get('service1');
    }    
    
    public function testRegisterFromDefinitionErrorClassIsNotInstantiable()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'Tlumx\Test\ServiceContainer\NotInstantiable'
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Unable to create instance of class: "Tlumx\Test\ServiceContainer\NotInstantiable"'
        );
        $c->get('service1');        
    }     
    
    public function testRegisterFromDefinitionErrorConstructorArgsIsNotExists()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'Tlumx\Test\ServiceContainer\A'
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Option "args" is not exists in definition array'
        );
        $c->get('service1');        
    }    
    
    public function testRegisterFromDefinitionErrorCanNotCallMethod()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'Tlumx\Test\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'invalidMethod' => []
            ]
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Can not call method "invalidMethod" from class: "Tlumx\Test\ServiceContainer\A"'
        );
        $c->get('service1');
    }    
    
    public function testRegisterFromDefinitionErrorInvalidArgs()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'Tlumx\Test\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => []
            ]
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Unable resolve parameter'
        );
        $c->get('service1');
    }    
    
    public function testRegisterFromDefinitionErrorArgsNotInContainer()
    {
        $c = new ServiceContainer();
        $c->register('service1', [
            'class' => 'Tlumx\Test\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [['ref'=>'invalid-service']]
            ]
        ]);
        $this->setExpectedException(
                'Tlumx\ServiceContainer\Exception\ContainerException',
                'Unable resolve parameter'
        );
        $c->get('service1');
    }     
    
    public function testRegisterFromDefinition()
    {
        $c = new ServiceContainer();
        $c->register('B', [
            'class' => 'Tlumx\Test\ServiceContainer\B',
            'args' => ['a' => ['ref' => 'A']]
        ]);
        $c->register('A', [
            'class' => 'Tlumx\Test\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [ ['ref'=>'this'] ]
            ]
        ], false);
        
        $this->assertInstanceOf('Tlumx\Test\ServiceContainer\B' , $c->get('B'));
        
        $b = $c->get('B');
        $a = $b->getA();
        
        $this->assertInstanceOf('Tlumx\Test\ServiceContainer\A' , $a);
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
    
    public function testUseAlias()
    {
        $c = new ServiceContainer();
        $c->register('B', [
            'class' => 'Tlumx\Test\ServiceContainer\B',
            'args' => ['a' => ['ref' => 'A']]
        ]);
        $c->register('A', [
            'class' => 'Tlumx\Test\ServiceContainer\A',
            'args' => ['some value'],
            'calls' => [
                'setContainer' => [ ['ref'=>'this'] ]
            ]
        ]);        
        $c->set('a', 'aaa');
        $c->setAlias('a-alias', 'a');
        $c->setAlias('B-alias', 'B');
        
        $this->assertEquals($c->get('a'), $c->get('a-alias'));
        $this->assertNotSame($c->get('A'), $c->get('B-alias'));
        $this->assertSame($c->get('B'), $c->get('B-alias'));
    }
    
}

class NotInstantiable
{
    private function __construct()
    {
        
    }
}

class A
{
    protected $some;
    
    protected $c;

    public function __construct($some)
    {
        $this->some = $some;        
    }
    
    public function getSome()
    {
        return $this->some;
    }
    
    public function setContainer(ServiceContainer $c)
    {
        $this->c = $c;
    }
    
    public function getContainer()
    {
        return $this->c;
    }
}

class B
{
    protected $a;

    public function __construct(A $a)
    {
        $this->a = $a;        
    }
    
    public function getA()
    {
        return $this->a;
    }
}