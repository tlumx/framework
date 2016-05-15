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

use Tlumx\Http\Response;
use Tlumx\Http\Stream;

class ResponseTest extends \PHPUnit_Framework_TestCase
{    
    protected $response;
    
    public function setUp()
    {
        $this->response = new Response();
    }
    
    public function testImplements()
    {
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $this->response);
    }    
    
    public function testGetStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }
    
    public function testWithStatusCode()
    {
        $response = $this->response->withStatus(404);
        $this->assertEquals(404, $response->getStatusCode());
    }    
    
    public function testStatusIs()
    {
        $response = $this->response->withStatus(201);
        $this->assertTrue($response->isEmpty());
        $response = $this->response->withStatus(204);
        $this->assertTrue($response->isEmpty());
        $response = $this->response->withStatus(304);
        $this->assertTrue($response->isEmpty());      
        
        $response = $this->response->withStatus(102);
        $this->assertTrue($response->isInformational());
        
        $response = $this->response->withStatus(200);
        $this->assertTrue($response->isOk());
        $this->assertTrue($response->isSuccessful());
        
        $response = $this->response->withStatus(208);
        $this->assertTrue($response->isSuccessful());        
        
        $response = $this->response->withStatus(302);
        $this->assertTrue($response->isRedirection());        
     
        $response = $this->response->withStatus(403);
        $this->assertTrue($response->isForbidden());        
        
        $response = $this->response->withStatus(404);
        $this->assertTrue($response->isNotFound());        
        
        $response = $this->response->withStatus(401);
        $this->assertTrue($response->isClientError());        

        $response = $this->response->withStatus(500);
        $this->assertTrue($response->isServerError());                
    }
    
    
    /**
     * @dataProvider invalidStatusCodes
     */
    public function testInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException');
        $response = $this->response->withStatus($code);
    }
    
    /**
     * @dataProvider invalidStatusCodes
     */
    public function testCreateObjectInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException');
        $response = new Response(null, $code);
    }    
    
    public function invalidStatusCodes()
    {
        return [
            ['aaa'],
            [null],
            [false],
            [10],
            [1000],
            [[404]],
        ];
    }    
 
    
    public function testReasonPhrase()
    {
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $response = $this->response->withStatus(404);
        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }
    
    public function testSetHeaderOnCreate()
    {
        $headers = [
            'X-Some' => 'foo'
        ];
        $response = new Response(null, 200, $headers);
        $this->assertEquals('foo', $response->getHeaderLine('X-Some'));
    }
    
    public function testProtocol()
    {
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
        $response = new Response(null, 200, [], '1');
        $this->assertEquals('1', $response->getProtocolVersion());
    }
        
    public function testSetBody()
    {        
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $this->response->getBody());
        
        $stream = new Stream(fopen('php://temp', 'r+'));        
        $response = new Response($stream);
                
        $this->assertEquals($stream, $response->getBody());                
    }
    
    public function testRedirect()
    {
        $this->response->redirect('/about');
        $this->assertEquals(302, $this->response->getStatusCode());
        $this->assertEquals('/about', $this->response->getHeaderLine('Location'));
    }
    
    public function testJsonBody()
    {
        $data = ['abcd' => 1234];
        $this->response->setJsonBody($data);
        $this->assertEquals(
                'application/json;charset=utf-8',
                $this->response->getHeaderLine('Content-Type')
        );
        
        $body = $this->response->getBody();
        $body->rewind();
        
        $this->assertEquals('{"abcd":1234}', $body->getContents());                        
    }
    
    public function testWrite()
    {        
        $this->assertEquals('', (string) $this->response->getBody());
        $this->response->write('some');
        $this->assertEquals('some', (string) $this->response->getBody());        
        $this->response->write(' else');
        $this->assertEquals('some else', (string) $this->response->getBody());
    }
    
    
    public function testToString()
    {
        $string = "HTTP/1.1 200 OK\n";
        $string .= "X-Some: foo\n\n";
        $string .= "some some";
        
        $stream = fopen('php://temp', 'r+');
        $streamObject = new Stream($stream);
        $response = new Response($streamObject, 200, ['X-Some'=>'foo'], '1.1');
        $response->write('some some');
        $this->expectOutputString($string);
        
        echo $response;
    }        
}
