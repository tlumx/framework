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

use Tlumx\Http\Uri;
use Tlumx\Http\Stream;
use Tlumx\Http\UploadedFile;
use Tlumx\Http\ServerRequest;

class ServerRequestTest extends \PHPUnit_Framework_TestCase
{
    protected $request;

    public function setUp()
    {
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $this->request = new ServerRequest('GET', $uri, $streamObject);
    }
    
    public function testImplements()
    {
        $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $this->request);
    }
    
    public function testServerParams()
    {
        $this->assertEquals([], $this->request->getServerParams());
        
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $request = new ServerRequest('GET', $uri, $streamObject, [], ['a'=>'b']);
        $this->assertEquals(['a'=>'b'], $request->getServerParams());
    }
    
    public function testQookieParams()
    {
        $this->assertEquals([], $this->request->getCookieParams());
        
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $request = new ServerRequest('GET', $uri, $streamObject, [], [], ['a'=>'b']);
        $this->assertEquals(['a'=>'b'], $request->getCookieParams());
        
        $request2 = $request->withCookieParams(['x'=>'y']);
        $this->assertEquals(['x'=>'y'], $request2->getCookieParams());
    }
    
    public function testQueryParams()
    {
        $this->assertEquals([], $this->request->getQueryParams());
        
        $request2 = $this->request->withQueryParams(['x'=>'y']);
        $this->assertEquals(['x'=>'y'], $request2->getQueryParams());
    }    
    
    public function testUploadedFiles()
    {
        $this->assertEquals([], $this->request->getUploadedFiles());
        
        $files = [
            'files' => new UploadedFile('some', 10, 0, 'some.txt', 'text/plain')
        ];        
        
        $request2 = $this->request->withUploadedFiles($files);
        $this->assertEquals($files, $request2->getUploadedFiles());
    }
    
    public function testParsedBody()
    {
        $this->assertEquals(null, $this->request->getParsedBody());
        
        $request2 = $this->request->withParsedBody(['a'=>'b']);
        $this->assertEquals(['a'=>'b'], $request2->getParsedBody());
    }    
    
    public function testAttributes()
    {
        $this->assertEquals([], $this->request->getAttributes());
        $this->assertEquals(null, $this->request->getAttribute('a'));

        $request2 = $this->request->withAttribute('a', 'b');
        $this->assertEquals(['a'=>'b'], $request2->getAttributes());        
        
        $this->assertEquals('b', $request2->getAttribute('a'));
        $this->assertEquals(123, $request2->getAttribute('c', 123));
        
        $request3 = $this->request->withoutAttribute('a');
        $this->assertEquals(null, $request3->getAttribute('a'));
    }
    
    
    protected function getFromGlobal()
    {
        // 'https://user1:pass1@some-server.com/foo/bar?foo=bar'
        
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'HTTPS' => 'on',
            'PHP_AUTH_USER' => 'user1',
            'PHP_AUTH_PW' => 'pass1',
            'REQUEST_URI' => '/foo/bar?foo=bar',
            'HTTP_HOST' => 'some-server.com',
            'SCRIPT_NAME' => 'index.php'            
        ];
        
        $_FILES = [
            'myfile' => [
                'name' => 'someimage.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpM0Jr9t',
                'error' => 0,
                'size' => 190414                                
            ]
        ];
        
        $_POST = [
            'a' => 'b'
        ];
        
        return ServerRequest::createFromGlobal();
    }
    
    public function testFromGlobal()
    {
        $request = $this->getFromGlobal();
        
        $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $request);
        
        $this->assertEquals($_SERVER, $request->getServerParams());
        
        $this->assertTrue(count($request->getUploadedFiles()) == 1);
        $this->assertEquals('https://user1:pass1@some-server.com/foo/bar?foo=bar',
                (string) $request->getUri() );
        $this->assertEquals('POST', $request->getMethod());                
        $this->assertEquals(['some-server.com'], $request->getHeader('host'));
        
        $this->assertEquals(['a'=>'b'], $request->getParsedBody());
    }
        
    public function testIsMethod()
    {
        $this->assertEquals('GET', $this->request->getMethod());
        
        $request = $this->request->withMethod('CONNECT');
        $this->assertEquals('CONNECT', $request->getMethod());
        $this->assertTrue($request->isConnect());

        $request = $this->request->withMethod('DELETE');
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertTrue($request->isDelete());
        
        $request = $this->request->withMethod('GET');
        $this->assertEquals('GET', $request->getMethod());
        $this->assertTrue($request->isGet());
        
        $request = $this->request->withMethod('HEAD');
        $this->assertEquals('HEAD', $request->getMethod());
        $this->assertTrue($request->isHead());
        
        $request = $this->request->withMethod('OPTIONS');
        $this->assertEquals('OPTIONS', $request->getMethod());
        $this->assertTrue($request->isOptions());
        
        $request = $this->request->withMethod('PATCH');
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertTrue($request->isPatch());
        
        $request = $this->request->withMethod('POST');
        $this->assertEquals('POST', $request->getMethod());
        $this->assertTrue($request->isPost());
        
        $request = $this->request->withMethod('PUT');
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertTrue($request->isPut());
        
        $request = $this->request->withMethod('TRACE');
        $this->assertEquals('TRACE', $request->getMethod());
        $this->assertTrue($request->isTrace());
        
        $request = $this->request->withHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->isXmlHttpRequest());
        $this->assertTrue($request->isAjax());
    }
        
    public function testGetClientIp()
    {        
        $this->assertEquals('', $this->request->getClientIp());
        
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $request = new ServerRequest('GET', $uri, $streamObject, [], [
            'REMOTE_ADDR' => '127.0.0.2'
        ]);        
        
        $this->assertEquals('127.0.0.2', $request->getClientIp());
    }
    
    public function testGetClientHost()
    {        
        $this->assertEquals('', $this->request->getClientHost());
        
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $request = new ServerRequest('GET', $uri, $streamObject, [], [
            'REMOTE_HOST' => 'mysrv'
        ]);        
        
        $this->assertEquals('mysrv', $request->getClientHost());
    }    
    
    public function testGetUserAgent()
    {
        $this->assertEquals(null, $this->request->getUserAgent());
        $this->assertEquals('MyAgent', $this->request->getUserAgent('MyAgent'));
        
        $request = $this->request->withHeader('User-Agent', 'SupperUserAgent');
        $this->assertEquals('SupperUserAgent', $request->getUserAgent());
    }
    
    public function testGetSetLanguages()
    {
        $request = $this->request;
        $this->assertEquals(array(), $request->getLanguages());
        
        $request = $this->request;
        $request->setLanguages(array('en', 'ua', 'ru'));
        $this->assertEquals(array('en', 'ua', 'ru'), $request->getLanguages());
        
        
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);
        
        $request = new ServerRequest('GET', $uri, $streamObject);        
        $request = $request->withHeader('Accept-Language', 'uk,ru;q=0.8,en-US;q=0.6,en;q=0.4');
        
        $this->assertEquals(array('uk', 'ru', 'en_US', 'en'), $request->getLanguages());

        $request = $this->request->withHeader('Accept-Language', 'uk,ru;q=0.8,en-US;q=0.6,en;q=0.4');
        $request->setLanguages(array('en', 'ua', 'ru'));
        $this->assertEquals(array('en', 'ua', 'ru'), $request->getLanguages());

        
        $request = new ServerRequest('GET', $uri, $streamObject);
        $request = $request->withHeader('Accept-Language', '');
        $this->assertEquals(array(), $request->getLanguages());
    }    

    public function testGetLanguage()
    {
        $this->assertEquals(null, $this->request->getLanguage());
                
        $uri = new Uri('http://server.loc');
        $stream = fopen('php://temp', 'wb+');
        $streamObject = new Stream($stream);        
        $request = new ServerRequest('GET', $uri, $streamObject);        
        
        $this->assertEquals('en', $request->getLanguage(array('en', 'uk', 'ru')));
        
        $request2 = $request->withHeader('Accept-Language', 'uk,ru;q=0.8,en-US;q=0.6,en;q=0.4');
        
        $this->assertEquals('uk', $request2->getLanguage());
        
        $request2 = $request->withHeader('Accept-Language', 'uk,ru;q=0.8,en-US;q=0.6,en;q=0.4');
        $this->assertEquals('de', $request2->getLanguage(array('de', 'pl')));        
        
        $this->assertEquals('en', $request2->getLanguage(array('de', 'pl', 'en')));
    }    

    public function testGetRefererUri()
    {
        $this->assertEquals(null, $this->request->getRefererUri());
        
        $request = $this->request->withHeader('Referer', 'localhost');
        
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $request->getRefererUri());
    }
}
