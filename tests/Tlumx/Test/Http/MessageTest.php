<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Http;

use Tlumx\Http\Message;
use Tlumx\Http\Stream;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    protected $message;
    
    public function setUp()
    {
        $this->message = new Message();
    }
        
    public function testImplements()
    {
        $this->assertInstanceOf('Psr\Http\Message\MessageInterface', $this->message);
    }
    
    public function testProtocolVersion()
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());        
    }
    
    public function testWithProtocolVersion()
    {
        $clone = $this->message->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $clone->getProtocolVersion());        
    }
    
    public function testWithHeaderAndGetHeader()
    {        
        $clone = $this->message->withHeader('X-Some', 'val1');        
        $this->assertEquals([], $clone->getHeader('Invalid'));
        $this->assertEquals(['val1'], $clone->getHeader('X-Some'));        
    }
    
    public function testWithAddedHeader()
    {        
        $clone = $this->message->withHeader('X-Some', 'val1');
        $this->assertEquals(['val1'], $clone->getHeader('X-Some'));
        $clone = $clone->withAddedHeader('X-Some', ['val2', 'val3']); 
        $this->assertEquals(['val1', 'val2', 'val3'], $clone->getHeader('X-Some'));
    }    
    
    public function testWithoutHeader()
    {
        $clone = $this->message->withHeader('X-Some', 'val1');
        $this->assertEquals(['val1'], $clone->getHeader('X-Some'));        
        $clone = $clone->withoutHeader('X-Some');
        $this->assertEquals([], $clone->getHeader('X-Some'));        
    }
    
    public function testGetHeaderLine()
    {
        $clone = $this->message->withHeader('X-Some', 'val1');
        $this->assertEquals('val1', $clone->getHeaderLine('X-Some'));
        $this->assertEquals('', $clone->getHeaderLine('Invalid'));        
    }
    
    public function testHasHeader()
    {
        $clone = $this->message->withHeader('X-Some', 'val1');
        $this->assertTrue($clone->hasHeader('X-Some'));
        $this->assertFalse($clone->hasHeader('Invalid'));        
    }
    
    public function testGetHeaders()
    {        
        $this->assertEquals([], $this->message->getHeaders());
        
        $clone = $this->message->withHeader('X-Some', 'val1');        
        $clone = $clone->withHeader('Http-Else', ['val2', 'val3']); 
        
        $this->assertEquals([
            'X-Some' => ['val1'],
            'Http-Else' => ['val2', 'val3']            
        ], $clone->getHeaders());        
    }
    
    public function testBody()
    {
        $streamObject = new Stream(fopen('php://temp', 'r+'));
        $clone = $this->message->withBody($streamObject);
        $this->assertEquals($streamObject, $clone->getBody());
        $streamObject->close();
    }                        
}