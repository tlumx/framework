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

class UriTest extends \PHPUnit_Framework_TestCase
{    
    public function testImplements()
    {
        $uri = new Uri();
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
    }    

    public function testErrorCreateUri()
    {        
        $this->setExpectedException(
                'InvalidArgumentException',
                'URI passed to constructor must be a string or null'
        );
        $uri = new Uri(123);
    }
    
    public function testGetMethodProperties()
    {
        $uri = new Uri('https://user1:pass1@some.server.loc:10000/foo?x=y#z');
        
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('user1:pass1@some.server.loc:10000', $uri->getAuthority());
        $this->assertEquals('user1:pass1', $uri->getUserInfo());
        $this->assertEquals('some.server.loc', $uri->getHost());
        $this->assertEquals(10000, $uri->getPort());
        $this->assertEquals('/foo', $uri->getPath());
        $this->assertEquals('x=y', $uri->getQuery());
        $this->assertEquals('z', $uri->getFragment());
    }
    
    public function testWithScheme()
    {
        $uri = new Uri();
        $uri = $uri->withScheme('http');
        $this->assertEquals('http', $uri->getScheme());
    }
    
    public function testErrorWithScheme()
    {
        $uri = new Uri();
        $this->setExpectedException('InvalidArgumentException', 'Invalid scheme');        
        $uri = $uri->withScheme('pppp');
    }    
    
    public function testWithUserInfo()
    {
        $uri = new Uri();
        $uri = $uri->withUserInfo('user');
        $this->assertEquals('user', $uri->getUserInfo());
        $uri = $uri->withUserInfo('user', 'pass');
        $this->assertEquals('user:pass', $uri->getUserInfo());
    }

    public function testWithHost()
    {
        $uri = new Uri();
        $uri = $uri->withHost('some-server.loc');
        $this->assertEquals('some-server.loc', $uri->getHost());
    }    
    
    public function testWithPort()
    {
        $uri = new Uri();
        $uri = $uri->withPort(80);
        $this->assertEquals(80, $uri->getPort());
    }     
    
    public function testErrorWithPort()
    {
        $uri = new Uri();
        $this->setExpectedException(
                'InvalidArgumentException',
                'Invalid port: 80000. Must be null or int between 1 and 65535'
        );
        $uri = $uri->withPort(80000);
    }    
    
    public function testWithPath()
    {
        $uri = new Uri();
        $uri = $uri->withPath('/some/foo');
        $this->assertEquals('/some/foo', $uri->getPath());
        $uri = $uri->withPath('');
        $this->assertEquals('', $uri->getPath());        
        $uri = $uri->withPath('/foo');
        $this->assertEquals('/foo', $uri->getPath());          
    }
    
    public function testErrorWithPath()
    {
        $uri = new Uri();
        $this->setExpectedException('InvalidArgumentException', 'The path must be a string');
        $uri = $uri->withPath([]);         
    }    
    
    public function testWithQuery()
    {
        $uri = new Uri();
        $uri = $uri->withQuery('foo=bar&x=y');
        $this->assertEquals('foo=bar&x=y', $uri->getQuery());        
        $uri = $uri->withQuery('');
        $this->assertEquals('', $uri->getQuery());        
        $uri = $uri->withQuery('?foo=bar');
        $this->assertEquals('foo=bar', $uri->getQuery());        
    }    
    
    public function testErrorWithQuery()
    {
        $uri = new Uri();
        $this->setExpectedException('InvalidArgumentException', 'The query must be a string');
        $uri = $uri->withQuery([]);        
    }    
    
    public function testWithFragment()
    {
        $uri = new Uri();
        $uri = $uri->withFragment('some');
        $this->assertEquals('some', $uri->getFragment());
        $uri = $uri->withFragment('#some');
        $this->assertEquals('some', $uri->getFragment());        
        $uri = $uri->withFragment('');
        $this->assertEquals('', $uri->getFragment());        
    }    
    
    public function testErrorWithFragment()
    {
        $uri = new Uri();
        $this->setExpectedException('InvalidArgumentException', 'The fragment must be a string');
        $uri = $uri->withFragment([]);        
    }    
    
    public function testToString()
    {
        $str = 'https://user1:pass1@some.server.loc:10000/foo?x=y#z';
        $uri = new Uri($str);
        
        $this->assertEquals($str, (string) $uri);
        
        $uri = $uri->withScheme('http');
        $uri = $uri->withUserInfo('u', 'p');
        $uri = $uri->withHost('localhost');
        $uri = $uri->withPort(80);
        $uri = $uri->withPath('some/page');
        $uri = $uri->withQuery('abc=123');
        $uri = $uri->withFragment('some');
        
        $this->assertEquals('http://u:p@localhost/some/page?abc=123#some', (string) $uri);
    }
        
    public function testCreateFromGlobals()
    {
        $_SERVER = [            
            'HTTPS' => 'on',
            'PHP_AUTH_USER' => 'user1',
            'PHP_AUTH_PW' => 'pass1',
            'REQUEST_URI' => '/foo/bar?foo=bar',
            'HTTP_HOST' => 'some-server.com',
            'SCRIPT_NAME' => 'index.php'
        ];
        
        $uri = Uri::createFromGlobals();
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
        
        $this->assertEquals('https://user1:pass1@some-server.com/foo/bar?foo=bar', (string) $uri);
    }
}

