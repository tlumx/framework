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

use Tlumx\Http\Request;
use Tlumx\Http\Stream;
use Tlumx\Http\Uri;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testInputMethod()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('PosT', $uri, $streamObject);
                        
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
        $this->assertEquals('POST', $request->getMethod());
        $request = $request->withMethod('get');
        $this->assertEquals('GET', $request->getMethod());
    }
    
    public function testErrorMethod()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        
        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        $request = new Request('AAA', $uri, $streamObject);
    }
    
    public function testInputUri()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('POST', $uri, $streamObject);
        
        $uri = $request->getUri();
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
        $newUri = new Uri('http://www.example.com');
        $request = $request->withUri($newUri);
        $uri = $request->getUri();
        $this->assertEquals($newUri, $uri);
    }

    public function testInputHeaders()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('GET', $uri, $streamObject, ['X-Some' => 'xval']);
        
        
        $this->assertEquals(['xval'], $request->getHeader('X-Some'));
        $this->assertEquals(['server.loc'], $request->getHeader('Host'));
    }    
    
    public function testInputBody()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('GET', $uri, $streamObject);        
        
        $this->assertEquals($streamObject, $request->getBody());
    }    
    
    public function testTarget()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('GET', $uri, $streamObject);

        $request2 = $request->withRequestTarget('foo');
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('foo', $request2->getRequestTarget());                
        
        $uri = new Uri('http://server.loc/foo?abc=123');
        $request = new Request('GET', $uri, $streamObject);
        $this->assertEquals('/foo?abc=123', $request->getRequestTarget());
    }
    
    public function testErrorSetTarger()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        fclose($stream);
        $request = new Request('GET', $uri, $streamObject);
        
        $this->setExpectedException('InvalidArgumentException', 'Invalid request target');       
        $request = $request->withRequestTarget('foo bar');
    }        
}
