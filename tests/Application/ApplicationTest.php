<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application;

use Tlumx\Application\Application;
use Tlumx\Application\ConfigureContainerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Tlumx\Application\Exception\BootstrapperClassNotFoundException;
use Tlumx\Application\Exception\InvalidBootstrapperClassException;
use Tlumx\Application\ApplicationEvent;
use Tlumx\View\TemplatesManager;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{

    public function tearDown()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function testInvalidInputParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input parameter "containerOrConfig": '.
            '"expected a ContainerInterface or congiguration array".');
        $app = new Application('not_array_and_not_container');
    }

    public function testConstructorConfigureContainerNull()
    {
        $app = new Application([]);

        $class = new \ReflectionClass($app);
        $propertyConfigureContainer = $class->getProperty('configureContainer');
        $propertyConfigureContainer->setAccessible(true);
        $configureObj = $propertyConfigureContainer->getValue($app);

        $this->assertInstanceOf(ConfigureContainerInterface::class, $configureObj);
        $this->assertEquals($configureObj, $app->getConfigureContainerObj());
    }

    public function testConstructorConfigureContainer()
    {
        $configureObject = $this->prophesize(ConfigureContainerInterface::class)->reveal();

        $app = new Application([], $configureObject);

        $class = new \ReflectionClass($app);
        $propertyConfigureContainer = $class->getProperty('configureContainer');
        $propertyConfigureContainer->setAccessible(true);
        $configureObj = $propertyConfigureContainer->getValue($app);

        $this->assertEquals($configureObject, $configureObj);
        $this->assertEquals($configureObject, $app->getConfigureContainerObj());
    }

    public function testSetConfigureContainer()
    {
        $app = new Application([]);

        $class = new \ReflectionClass($app);
        $propertyConfigureContainer = $class->getProperty('configureContainer');
        $propertyConfigureContainer->setAccessible(true);
        $configureObj = $propertyConfigureContainer->getValue($app);

        $this->assertInstanceOf(ConfigureContainerInterface::class, $configureObj);
        $this->assertEquals($configureObj, $app->getConfigureContainerObj());

        $newConfigureObj = $this->prophesize(ConfigureContainerInterface::class)->reveal();
        $app->setConfigureContainerObj($newConfigureObj);

        $this->assertEquals($newConfigureObj, $app->getConfigureContainerObj());
    }

    public function testConstructorSetContainer()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $app = new Application($container);

        $class = new \ReflectionClass($app);
        $propertyContainer = $class->getProperty('container');
        $propertyContainer->setAccessible(true);
        $containerObj = $propertyContainer->getValue($app);

        $this->assertInstanceOf(ContainerInterface::class, $containerObj);
        $this->assertEquals($container, $containerObj);
        $this->assertEquals($container, $app->getContainer());
    }

    public function testConstructorDefaultContainer()
    {
        $app = new Application([]);

        $class = new \ReflectionClass($app);
        $propertyContainer = $class->getProperty('container');
        $propertyContainer->setAccessible(true);
        $containerObj = $propertyContainer->getValue($app);

        $this->assertInstanceOf(ContainerInterface::class, $containerObj);
        $this->assertEquals($containerObj, $app->getContainer());
    }

    public function testConstructorDefaultConfigArray()
    {
        $app = new Application([]);

        $class = new \ReflectionClass($app);
        $propertyDefaultConfig = $class->getProperty('defaultConfig');
        $propertyDefaultConfig->setAccessible(true);
        $defaultConfig = $propertyDefaultConfig->getValue($app);

        $configObj = $app->getContainer()->get('config');
        $config = $configObj->getAll();

        $this->assertEquals($defaultConfig, $config);
    }

    public function testConstructorConfigArray()
    {
        // change not all default options for test & add fixed options
        $conf = [
            'some option' => 'some value',
            'error_reporting' => '1',
            'display_errors' => '0',
            'display_exceptions' => false,
            'new_option1' => 'val1',
            'new_option2' => 'val2'
        ];
        $app = new Application($conf);

        $class = new \ReflectionClass($app);
        $propertyDefaultConfig = $class->getProperty('defaultConfig');
        $propertyDefaultConfig->setAccessible(true);
        $defaultConfig = $propertyDefaultConfig->getValue($app);

        $configObj = $app->getContainer()->get('config');
        $config = $configObj->getAll();

        $this->assertEquals(array_merge($defaultConfig, $conf), $config);
    }

    public function testGetConfig()
    {
        // change not all default options for test & add fixed options
        $conf = [
            'some option' => 'some value',
            'error_reporting' => '0',
            'display_errors' => '0',
            'display_exceptions' => false,
            'new_option1' => 'val1',
            'new_option2' => 'val2'
        ];
        $app = new Application($conf);

        $class = new \ReflectionClass($app);
        $propertyDefaultConfig = $class->getProperty('defaultConfig');
        $propertyDefaultConfig->setAccessible(true);
        $defaultConfig = $propertyDefaultConfig->getValue($app);

        $configObj = $app->getContainer()->get('config');
        $config = $configObj->getAll();

        $this->assertEquals($configObj, $app->getConfig());
        $this->assertEquals('some value', $app->getConfig('some option'));
        $this->assertNull($app->getConfig('no isset option'));
        $this->assertEquals('default value', $app->getConfig('no isset option', 'default value'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testErrorHandler()
    {
        $app = new Application([]);
        error_reporting(-1);
        //$this->setExpectedException('ErrorException');
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('my error');
        $app->errorHandler(1, 'my error', 'file', 20);
    }

    /**
     * @runInSeparateProcess
     */
    public function testErrorHandlerNoException()
    {
        $app = new Application([]);
        error_reporting(0);
        $this->assertEquals(null, $app->errorHandler(1, 'my error', 'file', 20));
    }

    public function testBootstrapClassNotFound()
    {
        $app = new Application([
            'bootstrappers' => [
                'BootstrapClassNotFound'
            ]
        ]);
        $this->expectException(BootstrapperClassNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Bootstrapper class "%s" not found', 'BootstrapClassNotFound'));

        $class = new \ReflectionClass($app);
        $methodBootstrap = $class->getMethod('bootstrap');
        $methodBootstrap->setAccessible(true);
        $methodBootstrap->invokeArgs($app, []);
    }

    public function testBootstrapClassNotExtendBootstrapper()
    {
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\BootstrapClassNotExtendBootstrapper'
            ]
        ]);
        $this->expectException(InvalidBootstrapperClassException::class);
        $this->expectExceptionMessage(sprintf(
            "Bootstrapper class \"%s\" must extend from Tlumx\\Application\\Bootstrapper",
            'Tlumx\Tests\Application\Fixtures\BootstrapClassNotExtendBootstrapper'
        ));

        $class = new \ReflectionClass($app);
        $methodBootstrap = $class->getMethod('bootstrap');
        $methodBootstrap->setAccessible(true);
        $methodBootstrap->invokeArgs($app, []);
    }

    public function testBootstrapSuccess()
    {
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ],
            'a' => 'value_a',
            'b' => 'value_b',
            'c' => [
                'c1' => 'value_c1',
                'c2' => 'value_c2',
                'c3' => [
                    'c3_1' => 'value_c3_1',
                    'c3_2' => 'value_c3_3',
                ]
            ]
        ]);

        $class = new \ReflectionClass($app);
        $methodBootstrap = $class->getMethod('bootstrap');
        $methodBootstrap->setAccessible(true);
        $methodBootstrap->invokeArgs($app, []);

        // init()
        $this->assertTrue($app->getContainer()->has('init_service'));
        $this->assertEquals('init_service_value', $app->getContainer()->get('init_service'));

        // getServiceConfig
        $this->assertTrue($app->getContainer()->has('service1'));
        $this->assertEquals('value1', $app->getContainer()->get('service1'));

        // Bootstrapper not overide "main" config, can only add new options
        $config = $app->getConfig();
        $this->assertEquals([
            'a' => 'value_a',
            'b' => 'value_b',
            'c' => [
                'c1' => 'value_c1',
                'c2' => 'value_c2',
                'c3' => [
                    'c3_1' => 'value_c3_1',
                    'c3_2' => 'value_c3_3',
                    'c3_3' => 'value_c3_3_from_b',
                ]
            ],
            'd' => 'd_value',
            'error_reporting' => '-1',
            'display_errors' => '1',
            'display_exceptions' => true,
            'router_cache_enabled' => false,
            'router_cache_file' => 'routes.php.cache',
            'response_chunk_size' => 4096,
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ]
        ], $config->getAll());

        // testEvents
        $em = $app->getContainer()->get('event_manager');
        $event = new ApplicationEvent(ApplicationEvent::EVENT_POST_BOOTSTRAP);
        $event->setContainer($app->getContainer());
        $this->assertEquals('postBootstrap', $em->trigger($event));
        $event->setName(ApplicationEvent::EVENT_PRE_ROUTING);
        $this->assertEquals('preRouting', $em->trigger($event));
        $event->setName(ApplicationEvent::EVENT_POST_ROUTING);
        $this->assertEquals('postRouting', $em->trigger($event));
        $event->setName(ApplicationEvent::EVENT_PRE_DISPATCH);
        $this->assertEquals('preDispatch', $em->trigger($event));
        $event->setName(ApplicationEvent::EVENT_POST_DISPATCH);
        $this->assertEquals('postDispatch', $em->trigger($event));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetErrorsRun()
    {
        $app = new Application([]);
        $app->run(false);

        $this->assertEquals('-1', ini_get('error_reporting'));
        $this->assertEquals('1', ini_get('display_errors'));

        $this->expectException(\ErrorException::class);
        $original = unserialize('foo');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunMustReturnResponse()
    {
        $app = new Application([]);
        $response = $app->run(false);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function getController($container)
    {
        // Set templates
        $tm = new TemplatesManager();
        $layout = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SomeModule' .
                DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main.phtml';
        $layout2 = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'SomeModule' .
                DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main2.phtml';
        $tm->setTemplateMap([
            'main' => $layout,
            'main2' => $layout2
        ]);
        $tm->addTemplatePath('foo', __DIR__ . DIRECTORY_SEPARATOR . 'resources' .
            DIRECTORY_SEPARATOR . 'SomeModule' . DIRECTORY_SEPARATOR . 'views' .
            DIRECTORY_SEPARATOR . 'foo');
        $container->set('templates_manager', $tm);

        $config = $container->get('config');
        $config->set('layout', 'main');

        // Create controller object
        $fileController = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'SomeModule' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR .
            'FooController.php';
        require_once $fileController;
        $class = 'Foo\\FooController';
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunSuccess()
    {
        $_SERVER = [
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        ];
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ],
            'routes' => [
                'home' => [
                    'methods' => 'GET',
                    'pattern' => '/',
                    'middlewares' => [],
                    'handler' => ['controller' => 'FooController', 'action' => 'gamma'],
                ],
            ],
            'service_container' => [
                'factories' => [
                    'FooController' => 'Foo\\FooController',
                ]
            ]
        ]);

        $container = $app->getContainer();
        $em = $app->getContainer()->get('event_manager');
        $em->attach(ApplicationEvent::EVENT_POST_BOOTSTRAP, function (ApplicationEvent $e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_BOOTSTRAP.'service', 'do1');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_PRE_ROUTING, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_PRE_ROUTING.'service', 'do2');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_POST_ROUTING, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_ROUTING.'service', 'do3');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_PRE_DISPATCH, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_PRE_DISPATCH.'service', 'do4');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_POST_DISPATCH, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_DISPATCH.'service', 'do5');
        }, 10);
        $this->getController($app->getContainer());

        // run
        $response = $app->run(false);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals('gamma', $body->getContents());

        // testEvents - all event was tregged
        $this->assertEquals('do1', $container->get(ApplicationEvent::EVENT_POST_BOOTSTRAP.'service'));
        $this->assertEquals('do2', $container->get(ApplicationEvent::EVENT_PRE_ROUTING.'service'));
        $this->assertEquals('do3', $container->get(ApplicationEvent::EVENT_POST_ROUTING.'service'));
        $this->assertEquals('do4', $container->get(ApplicationEvent::EVENT_PRE_DISPATCH.'service'));
        $this->assertEquals('do5', $container->get(ApplicationEvent::EVENT_POST_DISPATCH.'service'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunWithoutOriginalDispatchMiddleware()
    {
        $_SERVER = [
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        ];
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ],
            'routes' => [
                'home' => [
                    'methods' => 'GET',
                    'pattern' => '/',
                    'middlewares' => [],
                    'handler' => ['controller' => 'FooController', 'action' => 'gamma'],
                ],
            ],
            'service_container' => [
                'factories' => [
                    'FooController' => 'Foo\\FooController',
                ]
            ]
        ]);

        $container = $app->getContainer();
        $em = $app->getContainer()->get('event_manager');
        $em->attach(ApplicationEvent::EVENT_POST_BOOTSTRAP, function (ApplicationEvent $e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_BOOTSTRAP.'service', 'do1');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_PRE_ROUTING, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_PRE_ROUTING.'service', 'do2');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_POST_ROUTING, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_ROUTING.'service', 'do3');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_PRE_DISPATCH, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_PRE_DISPATCH.'service', 'do4');
        }, 10);
        $em->attach(ApplicationEvent::EVENT_POST_DISPATCH, function ($e) {
            $e->getContainer()->set(ApplicationEvent::EVENT_POST_DISPATCH.'service', 'do5');
        }, 10);

        $this->getController($app->getContainer());

        $container->register(
            'DispatchMiddleware',
            "\Tlumx\Tests\Application\Fixtures\InvalidDispatchMiddleware"
        );

        $fileNotFoundHandler = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'MyNotFoundHandler.php';
        require_once $fileNotFoundHandler;
        $classNotFoundHandler = new \Tlumx\Tests\Application\MyNotFoundHandler();
        $container->set('not_found_handler', $classNotFoundHandler);

        // run
        $response = $app->run(false);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals('404', $body->getContents());

        // testEvents - all event was tregged
        $this->assertEquals('do1', $container->get(ApplicationEvent::EVENT_POST_BOOTSTRAP.'service'));
        $this->assertEquals('do2', $container->get(ApplicationEvent::EVENT_PRE_ROUTING.'service'));
        $this->assertEquals('do3', $container->get(ApplicationEvent::EVENT_POST_ROUTING.'service'));
        $this->assertEquals('do', $container->get('InvalidDispatchMiddlewareService'));
        $this->assertFalse($container->has(ApplicationEvent::EVENT_PRE_DISPATCH.'service'));
        $this->assertFalse($container->has(ApplicationEvent::EVENT_POST_DISPATCH.'service'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunCatchBlock()
    {
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ]
        ]);

        $em = $app->getContainer()->get('event_manager');
        $em->attach(ApplicationEvent::EVENT_POST_BOOTSTRAP, function (ApplicationEvent $e) {
            // check to use "ob_end_clean()" in catch block
            $notObFlush = new \Tlumx\Tests\Application\Fixtures\NotObFlush();

            $e->getContainer()->set(ApplicationEvent::EVENT_POST_BOOTSTRAP.'service', 'do1');
            throw new \Exception("This is my test exception");
        }, 10);

        $fileExceptionHandler = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'MyErrorHandler.php';
        require_once $fileExceptionHandler;
        $classErrorHandler = new \Tlumx\Tests\Application\MyErrorHandler();
        $app->getContainer()->set('exception_handler', $classErrorHandler);

        // run
        $response = $app->run(false);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals('Error: This is my test exception', $body->getContents());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRunAndSend()
    {
        $_SERVER = [
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        ];
        $app = new Application([
            'bootstrappers' => [
                'Tlumx\Tests\Application\Fixtures\ABootstrapper',
                'Tlumx\Tests\Application\Fixtures\BBootstrapper'
            ],
            'routes' => [
                'home' => [
                    'methods' => 'GET',
                    'pattern' => '/',
                    'middlewares' => [],
                    'handler' => ['controller' => 'FooController', 'action' => 'gamma'],
                ],
            ],
            'service_container' => [
                'factories' => [
                    'FooController' => 'Foo\\FooController',
                ]
            ]
        ]);
        $this->getController($app->getContainer());

        // run
        $app->run();
        $this->expectOutputString('gamma');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendResponse()
    {
        $response = new Response();
        $response = $response->withStatus(500);
        $response->getBody()->write('Error');
        $response->withHeader('Content-Type', 'text/html');

        $app = new Application([]);

        $app->sendResponse($response);
        $this->assertEquals(500, http_response_code());

        $this->expectOutputString('Error');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendResponseWithNoContentLength()
    {
        $response = new Response();
        $response = $response->withStatus(200);
        $response->getBody()->write('some content');
        $size = (string) $response->getBody()->getSize();

        $app = new Application([]);
        $app->sendResponse($response);

        $this->assertEquals(200, http_response_code());
        $this->expectOutputString('some content');
        $this->assertContains(
            'Content-Length: ' . $size,
            xdebug_get_headers()
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendResponseWithSetCookieHeaders()
    {
        $response = new Response();
        $response = $response->withStatus(200);
        $response->getBody()->write('some content');
        $response = $response->withHeader('Content-Type', 'text/html');
        $response = $response->withHeader('Set-Cookie', 'foo=bar');
        $response = $response->withAddedHeader('Set-Cookie', 'a=b');

        $app = new Application([]);
        $app->sendResponse($response);

        $this->assertEquals(200, http_response_code());
        $this->expectOutputString('some content');
        $this->assertContains('Content-Length: 12', xdebug_get_headers());
        $this->assertContains('Set-Cookie: foo=bar', xdebug_get_headers());
        $this->assertContains('Set-Cookie: a=b', xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendResponseWhenHeadersSent()
    {
        require __DIR__ . '/functions/headers_sent_function_helper.php';

        $response = new Response();
        $response = $response->withStatus(500);
        $response->getBody()->write('Error');
        $response->withHeader('Content-Type', 'text/html');

        $app = new Application([]);
        $app->sendResponse($response);

        $this->expectOutputString('');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendResponseBreakOnNotCconnectionNormal()
    {
        require __DIR__ . '/functions/connection_status_function_helper.php';

        $response = new Response();
        $response = $response->withStatus(500);
        $response->getBody()->write('Error');
        $response->withHeader('Content-Type', 'text/html');

        $app = new Application([]);
        $config = $app->getConfig();
        $config->set('response_chunk_size', 4);
        $app->sendResponse($response);

        $this->expectOutputString('Erro');
    }
}
