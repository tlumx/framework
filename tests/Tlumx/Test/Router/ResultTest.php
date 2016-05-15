<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Router;

use Tlumx\Router\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{    
    public function testResultFound()
    {
        $result = Result::createSuccessful(
                            'my-route', 
                            ['controller'=>'index', 'action'=>'index'], 
                            ['a'=>'b'],
                            ['midd1', 'midd2']
        );
        
        $this->assertTrue($result->isFound());
        $this->assertFalse($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertEquals('my-route', $result->getName());
        $this->assertEquals(['controller'=>'index', 'action'=>'index'], $result->getHandler());
        $this->assertEquals(['a'=>'b'], $result->getParams());
        $this->assertEquals(['midd1', 'midd2'], $result->getMiddlewares());
        $this->assertEquals([], $result->getAllowedMethods());        
    }

    public function testResultNotFound()
    {
        $result = Result::createFailure();
        
        $this->assertFalse($result->isFound());
        $this->assertTrue($result->isNotFound());
        $this->assertFalse($result->isMethodNotAllowed());
        $this->assertEquals('', $result->getName());
        $this->assertEquals([], $result->getHandler());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals([], $result->getMiddlewares());
        $this->assertEquals([], $result->getAllowedMethods());        
    }    
    
    public function testResultMethodNotAllwed()
    {
        $result = Result::createFailureMethod('my-route', ['GET', 'POST']);
        
        $this->assertFalse($result->isFound());
        $this->assertTrue($result->isNotFound());
        $this->assertTrue($result->isMethodNotAllowed());
        $this->assertEquals('my-route', $result->getName());
        $this->assertEquals([], $result->getHandler());
        $this->assertEquals([], $result->getParams());
        $this->assertEquals([], $result->getMiddlewares());
        $this->assertEquals(['GET', 'POST'], $result->getAllowedMethods());        
    }    
}