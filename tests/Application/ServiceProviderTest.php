<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Test\Application;

use Tlumx\Application\ServiceProvider;
//use Tlumx\Http\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Tlumx\Router\Router;
use Tlumx\Router\RouteCollector;
use Tlumx\EventManager\EventManager;
use Tlumx\View\View;
use Tlumx\View\TemplatesManager;
use Tlumx\Application\Handler\DefaultExceptionHandler;
use Tlumx\Application\Handler\DefaultNotFoundHandler;

class ServiceProviderTest extends \PHPUnit\Framework\TestCase
{
    protected $provider;

    public function setUp()
    {
       $_SERVER = array(
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        );        
       
       $this->provider = new ServiceProvider();       
    }
    
    public function testImplements()
    {        
        $this->assertInstanceOf('Tlumx\ServiceContainer\ServiceContainer', $this->provider);
    }
    
    public function testConfig()
    {
        $this->provider->setConfig('a', 123);        
        $this->assertEquals(123, $this->provider->getConfig('a'));
        $this->assertEquals(null, $this->provider->getConfig('not_exist'));
        $this->provider->setConfig(['b'=>456]);
        $this->assertEquals(456, $this->provider->getConfig('b'));
        $this->assertTrue(is_array($this->provider->getConfig()));        
    }
    
    public function testGetRequest()
    {
        $this->assertInstanceOf('\Psr\Http\Message\RequestInterface', $this->provider->getRequest());
    }
    
    public function testSetRequest()
    {
        $this->provider->register('request', function() { 
            $request = ServerRequestFactory::fromGlobals();
            return $request->withAttribute('a', 123);
        });
        $this->assertEquals(123, $this->provider->getRequest()->getAttribute('a'));
        $newRequest = $request = ServerRequestFactory::fromGlobals();
        $newRequest = $newRequest->withAttribute('b', 456);
        $this->provider->setRequest($newRequest);
        $this->assertEquals($newRequest, $this->provider->getRequest());
    }
    
    public function testGetResponse()
    {
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $this->provider->getResponse());
    }
    
    public function testSetResponse()
    {
        $this->provider->register('response', function() {
            $response = new Response();            
            return $response->withStatus(500);
        });
        $this->assertEquals(500, $this->provider->getResponse()->getStatusCode());
        $newResponse = new Response();
        $newResponse = $newResponse->withStatus(404);
        $this->provider->setResponse($newResponse);
        $this->assertEquals(404, $this->provider->getResponse()->getStatusCode());
    }
    
    public function testGetRouter()
    {
        $this->assertInstanceOf('\Tlumx\Router\RouterInterface', $this->provider->getRouter());
    }
    
    public function getRouteDefinitionCallback()
    {
        return function (RouteCollector $r) {
            $r->addRoute(
                'foo',
                ['GET','POST'],
                '/foo',
                ['midd1', 'midd2'],
                ['_controller' => 'home','_action' => 'index']
            );
            $r->addRoute(
                'article',
                ['GET'],
                '/articles/{id:\d+}[/{title}]',
                ['midd1', 'midd2'],
                ['article_handler'],
                'adm'
            );
            $r->addGroup('adm', '/admin', ['adm_midd1', 'adm_midd2']);
        };
    }

    public function testSetRouter()
    {
        $this->provider->register('router', function() {
            $routeDefinitionCallback = $this->getRouteDefinitionCallback();
            $router = new Router($routeDefinitionCallback);            
            return $router;
        });
        $this->assertEquals('/foo', $this->provider->getRouter()->uriFor('foo'));

        $routeDefinitionCallback = $this->getRouteDefinitionCallback();
        $newRouter = new Router($routeDefinitionCallback);            
        $this->provider->setRouter($newRouter);               
        

        $this->assertEquals('/admin/articles/10', $this->provider->getRouter()->uriFor('article', ['id' => '10']));
    }
    
    public function testGetEventManager()
    {
        $this->assertInstanceOf('\Tlumx\EventManager\EventManager', $this->provider->getEventManager());
    }
    
    public function testSetEventManager()
    {
        $eventManager = new EventManager();
        $eventManager->attach('my', function() {
            // ...
        });
        $this->provider->setEventManager($eventManager);
        $newEm = $this->provider->getEventManager();

        $class = new \ReflectionClass($newEm);
        $property = $class->getProperty('listeners');
        $property->setAccessible(true);
        $this->assertArrayHasKey('my', $property->getValue($newEm));        
    }
    
    public function testGetView()
    {
        $this->assertInstanceOf('\Tlumx\View\ViewInterface', $this->provider->getView());
    }
    
    public function testSetView()
    {
        $this->provider->register('view', function() {
            $view = new View();
            $view->a = 123;
            return $view;
        });
        $this->assertEquals(123, $this->provider->getView()->a);
        $view = new View();
        $view->b = 456;
        $this->provider->setView($view);
        $this->assertEquals(456, $this->provider->getView()->b);
    }
    
    public function testGetTemplatesManager()
    {
        $this->assertInstanceOf('\Tlumx\View\TemplatesManager', $this->provider->getTemplatesManager());
    }
    
    public function testSetTemplatesManager()
    {
        $templatesManager = new TemplatesManager();
        $templatesManager->addTemplate('my', __FILE__);
        $this->provider->setTemplatesManager($templatesManager);
        $this->assertTrue($this->provider->getTemplatesManager()->hasTemplate('my'));
    }
    
    public function testGetExceptionHandler()
    {
        $this->assertInstanceOf('\Tlumx\Application\Handler\ExceptionHandlerInterface', $this->provider->getExceptionHandler());
    }
    
    public function testSetExceptionHandler()
    {
        $provider = $this->provider;
        $this->provider->register('exception_handler', function() use($provider) {
            return new DefaultExceptionHandler($provider);
        });
        $this->assertInstanceOf('\Tlumx\Application\Handler\ExceptionHandlerInterface', $this->provider->getExceptionHandler());        
    }
    
    public function testGetNotFoundHandler()
    {
        $this->assertInstanceOf('\Tlumx\Application\Handler\NotFoundHandlerInterface', $this->provider->getNotFoundHandler());
    }
    
    public function testSetNotFoundHandler()
    {
        $provider = $this->provider;
        $this->provider->register('not_found_handler', function() use($provider) {
            return new DefaultNotFoundHandler($provider);
        });
        $this->assertInstanceOf('\Tlumx\Application\Handler\NotFoundHandlerInterface', $this->provider->getNotFoundHandler());        
    }    
}