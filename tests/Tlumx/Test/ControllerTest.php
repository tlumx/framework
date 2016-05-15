<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test;

use Tlumx\ServiceProvider;
use Tlumx\View\TemplatesManager;
use Tlumx\Router\Router;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    protected $controller;
    
    protected $provider;

    public function setUp()
    {
        $_SERVER = array(
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        );        
        
        $this->provider = new ServiceProvider();
        
        $router = new Router();
        $router->setRoutes([
            'my' => [
                'methods' => ['GET'],
                'route' => '/my/{id}',
                'handler' => [],
                'filters' => ['id' => '([\d-]+)'],
                'child_routes' => [
                    'my-sub' => [
                        'methods' => ['GET', 'POST'],
                        'route' => '/sub/{p}',
                        'handler' => [],
                        'filters' => ['p'=>'(\d+)']
                    ]
                ]
            ]            
        ]);        
        $this->provider->setRouter($router);
        
        $tm = new TemplatesManager();
        $layout = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SomeModule' .
                DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main.phtml';        
        $layout2 = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SomeModule' .
                DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main2.phtml';         
        $tm->setTemplateMap([
            'main' => $layout,
            'main2'=> $layout2
        ]);
        $tm->addTemplatePath('foo', __DIR__ . DIRECTORY_SEPARATOR . 'resources'.DIRECTORY_SEPARATOR.'SomeModule'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'foo');
        $this->provider->setTemplatesManager($tm);
        
       
        $this->provider->setConfig([
            'layout' => 'main',           
        ]);
        
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SomeModule' . 
                DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'FooController.php';        
        require_once $file;
        $class = 'Foo\\FooController';
        $this->controller = new $class($this->provider);                
    }
    
    public function tearDown()
    {
        unset($this->controller);
        unset($this->provider);
    }
    
    public function testGetServiceProvider()
    {
        $this->assertEquals($this->provider, $this->controller->getServiceProvider());
    }

    public function testGetView()
    {
        $tm = $this->provider->getTemplatesManager();
        $path = $tm->getTemplatePath('foo');
        $tm->addTemplatePath('index', $path);

        $view = $this->provider->getView();
        $this->assertEquals($view, $this->controller->getView());
        $this->assertEquals(rtrim($path, DIRECTORY_SEPARATOR), $this->controller->getView()->getTemplatesPath());
    }
    
    public function testRender()
    {
        $tm = $this->provider->getTemplatesManager();
        $path = $tm->getTemplatePath('foo');
        $tm->addTemplatePath('index', $path);
        
        $content = $this->controller->render();
        
        $this->assertEquals("Hello!", $content);        
    }
    
    public function testLayout()
    {
        $this->assertFalse($this->controller->enableLayout());
        $this->controller->enableLayout(true);
        $this->assertTrue($this->controller->enableLayout());
        $this->controller->enableLayout(false);
        $this->assertFalse($this->controller->enableLayout());
        $this->assertEquals(null, $this->controller->getLayout()); 
        $this->controller->setLayout('main');
        $this->assertEquals('main', $this->controller->getLayout());
        $this->assertTrue($this->controller->enableLayout());
    }
    
    public function testCreatePath()
    {                
        $path = $this->controller->createPath('my-sub', ['id'=>2, 'p'=>'3'], ['a'=>'b','x'=>'y']);
        
        $this->assertEquals('/my/2/sub/3?a=b&x=y', $path);
    }
    
    public function testRedirect()
    {
        $this->controller->redirect('/login', $status = 302);
        
        $this->assertEquals(302, $this->controller->getServiceProvider()->getResponse()->getStatusCode());
        $this->assertEquals('/login', $this->controller->getServiceProvider()->getResponse()->getHeaderLine('Location'));
    }
    
    public function testRedirectToRoute()
    {
        $this->controller->redirectToRoute('my-sub', ['id'=>2, 'p'=>'3'], ['a'=>'b','x'=>'y'], 302);
        
        $this->assertEquals(302, $this->controller->getServiceProvider()->getResponse()->getStatusCode());
        $this->assertEquals('/my/2/sub/3?a=b&x=y', $this->controller->getServiceProvider()->getResponse()->getHeaderLine('Location'));        
    }
    
    
    /**
     * @runInSeparateProcess
     */
    public function testRun()
    {
        $handler = [
            'controller' => 'foo',
            'action' => 'delta'
        ];
        
        $expected = '<div>a=some;b=123;</div>';
        
        $request = $this->provider->getRequest();
        $request = $request->withAttribute('router_result_handler', $handler);
        $this->controller->getServiceProvider()->setRequest($request);
        
        $this->controller->run();
        $actualResponse = $this->provider->getResponse();
        $body = $actualResponse->getBody();
        $body->rewind();        
        
        $this->assertEquals($expected, $body->getContents());
    } 
    
    /**
     * @runInSeparateProcess
     */
    public function testRun1()
    {
        $handler = [
            'controller' => 'foo',
            'action' => 'alpha'
        ];        
        
        $expected = '<html><body><div></div></body></html>'; 
        
        $request = $this->provider->getRequest();
        $request = $request->withAttribute('router_result_handler', $handler);
        $this->controller->getServiceProvider()->setRequest($request);
        
        $this->controller->run();
        $actualResponse = $this->provider->getResponse();
        $body = $actualResponse->getBody();
        $body->rewind();        
        
        $this->assertEquals($expected, $body->getContents());       
    }

    /**
     * @runInSeparateProcess
     */
    public function testRun2()
    {
        $handler = [
            'controller' => 'foo',
            'action' => 'beta'
        ];        
        
        $expected = '<html><body><div>beta</div></body></html>';        

        $request = $this->provider->getRequest();
        $request = $request->withAttribute('router_result_handler', $handler);
        $this->controller->getServiceProvider()->setRequest($request);
        
        $this->controller->run();
        $actualResponse = $this->provider->getResponse();
        $body = $actualResponse->getBody();
        $body->rewind();        
        
        $this->assertEquals($expected, $body->getContents());        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testRun3()
    {
        $handler = [
            'controller' => 'foo',
            'action' => 'gamma'
        ];         
        
        $expected = 'gamma';        
        
        $request = $this->provider->getRequest();
        $request = $request->withAttribute('router_result_handler', $handler);
        $this->controller->getServiceProvider()->setRequest($request);
        
        $this->controller->run();
        $actualResponse = $this->provider->getResponse();
        $body = $actualResponse->getBody();
        $body->rewind();        
        
        $this->assertEquals($expected, $body->getContents());         
    }    
}