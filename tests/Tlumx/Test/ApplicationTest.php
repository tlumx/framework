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

use Tlumx\Application;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{    
    public function setUp()
    {
       $_SERVER = array(
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        );              
    }
    
    protected function getMyExceptionHandler()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'MyErrorHandler.php';
        require_once $file;
        $class = 'Tlumx\Test\MyErrorHandler'; 
        
        return new $class();
    }    
    
    protected function getMyNotFoundHandler()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'MyNotFoundHandler.php';
        require_once $file;
        $class = 'Tlumx\Test\MyNotFoundHandler'; 
        
        return new $class();        
    }


    public function testConfig()
    {
        $app = new Application(['a'=>123]); 
        $this->assertEquals(123, $app->getConfig('a'));
        $this->assertTrue(is_array($app->getConfig()));
        $app->setConfig('foo', 'baz');
        $this->assertEquals('baz', $app->getConfig('foo'));
        $app->setConfig(array('asd' => 'some'));
        $this->assertEquals('some', $app->getConfig('asd'));        
    }
    
    public function testGetServiceProvider()
    {
        $app = new Application();
        $this->assertInstanceOf('Tlumx\ServiceContainer\ServiceContainer', $app->getServiceProvider());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testDefaultExceptionHandler()
    {
        $app = new Application();                
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/', []);
        $app->setConfig('display_exceptions', false);
        $app->run();
        
        $body = "<h1>An error occurred</h1><h2>Internal Server Error</h2>";        
        $result = sprintf("<html><head><title>%s</title><style>body {font-family: Helvetica,Arial,sans-serif;font-size: 20px;line-height: 28px;padding:20px;}</style></head><body>%s</body></html>",
            'Tlumx application: Internal Server Error', $body);
        
        $this->expectOutputString($result);
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testMyExceptionHandler()
    {
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/', []);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());       
        $app->run();
        
        $this->expectOutputString("Error: Controller \"index\" not exist.");
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testErrorHandler()
    {
        $app = new Application();        
        error_reporting(-1);                
        $this->setExpectedException('ErrorException');
        $app->errorHandler(1, 'my error', 'file', 20);
    }    
 
    /**
     * @runInSeparateProcess
     */
    public function testErrorHandlerNoException()
    {
        $app = new Application();
        error_reporting(0);
        $this->assertEquals(null, $app->errorHandler(1, 'my error', 'file', 20));
    } 

    /**
     * @runInSeparateProcess 
     */
    public function testBootstrapperClassNotFound()
    {
        $app = new Application([
            'bootstrappers' => [
                'MyBootstrap'
            ]
        ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();

        $errMessage = "Error: Bootstrapper class \"MyBootstrap\" not found";   
        $this->expectOutputString($errMessage);          
    }
    
    /**
     * @runInSeparateProcess 
     */
    public function testInvalidBootstrapClass()
    {
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Test\MyNotFoundHandler'
            ]
        ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();

        $errMessage = "Error: Bootstrapper class \"Tlumx\Test\MyNotFoundHandler\" not found";   
        $this->expectOutputString($errMessage);          
    }    
    
    
    /**
     * @runInSeparateProcess 
     */
    public function testNoFoundRoute()
    {
        $app = new Application();
        $app->getServiceProvider()->set('not_found_handler', $this->getMyNotFoundHandler());
        $app->run();

        $errMessage = "404";   
        $this->expectOutputString($errMessage);          
    }    

    /**
     * @runInSeparateProcess 
     */
    public function testNoAllowedMethod()
    {
        $app = new Application();
        $app->getServiceProvider()->set('not_found_handler', $this->getMyNotFoundHandler());
        $app->getServiceProvider()->getRouter()->setRoute('index', ['POST'], '/', []);
        $app->run();

        $errMessage = "Methods: POST";   
        $this->expectOutputString($errMessage);          
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidRouteMiddlewares()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', [], [], [ null ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();  
        
        $errMessage = "Error: Middleware is not callable";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidRouteMiddlewareInvalidReturn()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', [], [], [ function() {} ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());        
        $app->run();  
        
        $errMessage = "Error: Middleware must return instance of \Psr\Http\Message\ResponseInterface";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidRouteResultInRequest()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', [], [], [ function($req, $res, $next) {
            $req = $req->withoutAttribute('router_result');
            return $next($req, $res);            
        } ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());        
        $app->run(); 
        
        $errMessage = "Error: Controller \"index\" not exist.";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidControllerInRouteHandler()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => false]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();  
        
        $errMessage = "Error: Invalid controller name in route handler";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidActionInRouteHandler()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['action' => false]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();  
        
        $errMessage = "Error: Invalid action name in route handler";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidControllerClasNotExist()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application();
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', []);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();  
        
        $errMessage = "Error: Controller \"index\" not exist.";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidController()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );          
        $app = new Application([
            'controllers' => [
                'index' => __CLASS__
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', []);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        $app->run();  
        
        $errMessage = "Error: Controller \"Tlumx\Test\ApplicationTest\" is not an instance of Tlumx\Controller.";   
        $this->expectOutputString($errMessage);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testRun()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $response = $app->run();  
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);        
        
        $message = "about";   
        $this->expectOutputString($message);        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testRunNotSend()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $response = $app->run(false);  
        $this->assertEquals('about', $response->getBody()); 
        
        $this->expectOutputString('');       
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testCheckEvents()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $events = array();
        $app->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_BOOTSTRAP, function ($event) use (&$events) {
            $events[] = Application::EVENT_POST_BOOTSTRAP;
        });                  
        $app->getServiceProvider()->getEventManager()->addListener(Application::EVENT_PRE_ROUTING, function ($event) use (&$events) {
            $events[] = Application::EVENT_PRE_ROUTING;
        });         
        $app->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_ROUTING, function ($event) use (&$events) {
            $events[] = Application::EVENT_POST_ROUTING;
        });         
        $app->getServiceProvider()->getEventManager()->addListener(Application::EVENT_PRE_DISPATCH, function ($event) use (&$events) {
            $events[] = Application::EVENT_PRE_DISPATCH;
        });         
        $app->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_DISPATCH, function ($event) use (&$events) {
            $events[] = Application::EVENT_POST_DISPATCH;
        });        
        
        $app->run(false);        
        $e = array(
            Application::EVENT_POST_BOOTSTRAP,
            Application::EVENT_PRE_ROUTING,
            Application::EVENT_POST_ROUTING,
            Application::EVENT_PRE_DISPATCH,
            Application::EVENT_POST_DISPATCH
        );
        foreach ($e as $k => $value) {
            $this->assertEquals($events[$k], $value);
        }       
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testMiddlewares()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about'], [], [
                    function ($req, $res, $next) {
                        $res->write('Rm1Start');
                        $res = $next($req, $res);
                        $res->write('Rm1End');
                        return $res;
                    },
                    function ($req, $res, $next) {
                        $res->write('Rm2Start');
                        $res = $next($req, $res);
                        $res->write('Em2End');
                        return $res;
                    },             
        ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $app->add(function ($req, $res, $next) {
            $res->write('AM1Start');
            $res = $next($req, $res);
            $res->write('AM1End');
            return $res;
        });
        $app->add(function ($req, $res, $next) {
            $res->write('AM2Start');
            $res = $next($req, $res);
            $res->write('AM2End');
            return $res;
        });        
        $app->run();  
        
        $this->expectOutputString('AM1StartAM2StartRm1StartRm2StartaboutEm2EndRm1EndAM2EndAM1End');        
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidAppMiddlewares()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about'], [], [
                    function ($req, $res, $next) {
                        $res->write('Rm1Start');
                        $res = $next($req, $res);
                        $res->write('Rm1End');
                        return $res;
                    },
                    function ($req, $res, $next) {
                        $res->write('Rm2Start');
                        $res = $next($req, $res);
                        $res->write('Em2End');
                        return $res;
                    },             
        ]);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $app->add(function ($req, $res, $next) {
            $res->write('AM1Start');
            $res = $next($req, $res);
            $res->write('AM1End');
        });
        $app->add(function ($req, $res, $next) {
            $res->write('AM2Start');
            $res = $next($req, $res);
            $res->write('AM2End');
        });         
        $app->run();  
        
        
        $this->expectOutputString('Error: Middleware must return instance of \Psr\Http\Message\ResponseInterface');         
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testAddMiddlewareFromService()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $app->getServiceProvider()->set('midd1', function($req, $res, $next){
            $res->write('AM1Start');
            $res = $next($req, $res);
            $res->write('AM1End');
            return $res;
        });
        $app->add('midd1');
        $app->run();  
        
        
        $this->expectOutputString('AM1StartaboutAM1End');         
    }     
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidMiddlewareFromService()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $app->getServiceProvider()->set('midd1', 'invalid');
        $app->add('midd1');
        $app->run();  
        
        
        $this->expectOutputString('Error: Middleware "midd1" is not invokable');         
    }    
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidAddMiddlewareInNotCallable()
    {
        $app = new Application();
        $this->setExpectedException('InvalidArgumentException', 'Middleware is not callable');
        $app->add([]);          
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidAddMiddlewaresWhenCall()
    {
        $_SERVER = array(
            'REQUEST_METHOD'  => 'GET',
            'HTTP_HOST'     =>  'site.com',
            'REQUEST_URI'   =>  '/some-router',
        );
        
        require __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'FooController.php';
        
        $app = new Application([
            'controllers' => [
                'foo' => 'Foo\FooController'
            ]
        ]);
        $app->getServiceProvider()->getRouter()->setRoute('index', ['GET'], '/some-router', ['controller' => 'foo', 'action'=>'about']);
        $app->getServiceProvider()->set('exception_handler', $this->getMyExceptionHandler());
        
        $app->add(function ($req, $res, $next) {
            $res->write('AM1Start');
            $res = $next($req, $res);
            $res->write('AM1End');
            return $res;
        });
        $app->add(function ($req, $res, $next) use($app) {
            $app->add(function($req, $res, $next){
                return $next($req, $res);
            });
            $res = $next($req, $res);
        });         
        $app->run();  
        
        
        $this->expectOutputString('Error: Middleware can’t be added');         
    }         
}