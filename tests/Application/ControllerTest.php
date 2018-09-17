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

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Tlumx\ServiceContainer\FactoryInterface;
use Tlumx\View\TemplatesManager;
use Tlumx\Router\Router;
use Tlumx\Router\Result as RouteResult;
use Tlumx\Application\ConfigureTlumxContainer;
use Tlumx\Application\DefaultContainerFactory;
use Tlumx\ServiceContainer\ServiceContainer;

class ControllerTest extends \PHPUnit\Framework\TestCase
{
    protected $controller;

    protected $container;

    public function setUp()
    {
        $factory = new DefaultContainerFactory();
        $this->container = $factory->create([]);

        // Set routes for testing
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => [
                'methods' => ['GET','POST'],
                'pattern' => '/foo',
                'middlewares' => ['midd1', 'midd2'],
                'handler' => ['controller' => 'home','action' => 'index']
            ],
            'article' => [
                'methods' => ['GET'],
                'pattern' => '/articles/{id:\d+}[/{title}]',
                'middlewares' => ['midd1', 'midd2'],
                'handler' => ['controller' => 'article','action' => 'edit'],
                'group' => 'adm'
            ]
        ]);
        $config->set('routes_groups', [
            'adm' => [
                'prefix' => '/admin',
                'middlewares' => ['adm_midd1', 'adm_midd2']
            ]
        ]);

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
        $this->container->set('templates_manager', $tm);

        $config->set('layout', 'main');

        // Create controller object
        $fileController = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR .
            'SomeModule' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR .
            'FooController.php';
        require_once $fileController;
        $class = 'Foo\\FooController';

        $factory = new $class();
        $this->controller  = $factory($this->container);
    }

    public function tearDown()
    {
        unset($this->controller);
        unset($this->container);
    }

    public function testImplements()
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->controller);
        $this->assertInstanceOf(FactoryInterface::class, $this->controller);
    }

    public function testGetContainer()
    {
        $this->assertEquals($this->container, $this->controller->getContainer());
    }

    public function testGetView()
    {
        $tm = $this->container->get('templates_manager');
        $path = $tm->getTemplatePath('foo');
        $tm->addTemplatePath('index', $path);

        $view = $this->container->get('view');
        $this->assertEquals($view, $this->controller->getView());
        $this->assertEquals(rtrim($path, DIRECTORY_SEPARATOR), $this->controller->getView()->getTemplatesPath());
    }

    public function testRender()
    {
        $tm = $this->container->get('templates_manager');
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

    public function testInvalidSetLayout()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Layout name must be not empty string.');
        $this->controller->setLayout('');
    }

    public function testCreateUriFor()
    {
        $path = $this->controller->uriFor('article', ['id' => '10', 'title' => 'my-story'], ['x' => 100, 'y' => 'z']);

        $this->assertEquals('/admin/articles/10/my-story?x=100&y=z', $path);
    }

    public function testRedirect()
    {
        $response = $this->controller->redirect('/login', $status = 302);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaderLine('Location'));
    }

    public function testRedirectToRoute()
    {
        $response = $this->controller->redirectToRoute('article', [
            'id' => '10',
            'title' => 'my-story'
        ], ['x' => 100, 'y' => 'z'], 302);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/admin/articles/10/my-story?x=100&y=z', $response->getHeaderLine('Location'));
    }


    /**
     * @runInSeparateProcess
     */
    public function testHandle()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'delta']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $actualResponse = $this->controller->handle($request);
        $body = $actualResponse->getBody();
        $body->rewind();

        $expected = '<div>a=some;b=123;</div>';
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleNoRouteResultSet()
    {
        $request = $this->container->get('request');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Must be a valid \"Tlumx\Router\Result\" objrct in the request attriute");
        $this->controller->handle($request);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleActionNotFound()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'invalid']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Action "%s" not found.',
            'invalid'
        ));
        $this->controller->handle($request);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleActionReturnResponse()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'resp']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $actualResponse = $this->controller->handle($request);
        $body = $actualResponse->getBody();
        $body->rewind();

        $expected = 'from resp action';
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandle1()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'alpha']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $actualResponse = $this->controller->handle($request);
        $body = $actualResponse->getBody();
        $body->rewind();

        $expected = '<html><body><div></div></body></html>';
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandle2()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'beta']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $actualResponse = $this->controller->handle($request);
        $body = $actualResponse->getBody();
        $body->rewind();

        $expected = '<html><body><div>beta</div></body></html>';
        $this->assertEquals($expected, $body->getContents());
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandle3()
    {
        $result = RouteResult::createSuccess(
            'my-route',
            ['a' => 10, 'b' => 'str1'],
            ['GET', 'POST'],
            ['midd1', 'midd2'],
            ['controller' => 'foo', 'action' => 'gamma']
        );
        $request = $this->container->get('request');
        $request = $request->withAttribute(RouteResult::class, $result);
        $actualResponse = $this->controller->handle($request);
        $body = $actualResponse->getBody();
        $body->rewind();

        $expected = 'gamma';
        $this->assertEquals($expected, $body->getContents());
    }
}
