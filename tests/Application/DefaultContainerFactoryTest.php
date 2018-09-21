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

use Tlumx\Application\DefaultContainerFactory;
use Tlumx\ServiceContainer\ServiceContainer;
use Tlumx\Application\ConfigureTlumxContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Tlumx\Router\RouterInterface;
use Tlumx\EventManager\EventManagerInterface;
use Tlumx\View\ViewInterface;
use Tlumx\Application\Handler\ExceptionHandlerInterface;
use Tlumx\Application\Handler\NotFoundHandlerInterface;
use Tlumx\View\TemplatesManager;
use Tlumx\Application\Middleware\RouteMiddleware;
use Tlumx\Application\Middleware\DispatchMiddleware;
use Tlumx\Application\Exception\InvalidRouterConfigurationException;

class DefaultContainerFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $container;

    public function setUp()
    {
        $_SERVER = [
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        ];

        $factory = new DefaultContainerFactory();
        $this->container = $factory->create([]);
    }

    public function createRequest(string $uriPath = '/', string $method = 'GET')
    {
        $uri = $this->prophesize(UriInterface::class);
        $uri->getPath()->willReturn($uriPath);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->will(function () use ($uri) {
            return $uri->reveal();
        });
        $request->getMethod()->willReturn($method);

        return $request->reveal();
    }

    public function testImplements()
    {
        $this->assertInstanceOf(ServiceContainer::class, $this->container);
    }

    public function testCreateWithConfigureTlumxContainer()
    {
        $configureTlumxContainer = new ConfigureTlumxContainer();
        $factory = new DefaultContainerFactory();
        $container = $factory->create([
                'service_container' => [
                    'services' => [
                        'service1' => 'value1'
                    ]
                ]
            ], $configureTlumxContainer);
        $this->assertEquals('value1', $container->get('service1'));
    }

    public function testConfig()
    {
        $this->assertFalse($this->container->get('config')->has('a'));
        $this->container->get('config')->set('a', 123);
        $this->assertEquals(123, $this->container->get('config')->get('a'));

        $d = new DefaultContainerFactory();
        $newContainer = $d->create(['b' => 456]);
        $this->assertEquals(456, $newContainer->get('config')->get('b'));
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf(ServerRequestInterface::class, $this->container->get('request'));
    }

    public function testGetRequestAlias()
    {
        $this->assertTrue($this->container->hasAlias(ServerRequestInterface::class));
        $this->assertEquals('request', $this->container->getServiceIdFromAlias(ServerRequestInterface::class));
    }

    public function testGetResponse()
    {
        $this->assertInstanceOf(ResponseInterface::class, $this->container->get('response'));
    }

    public function testGetResponseAlias()
    {
        $this->assertTrue($this->container->hasAlias(ResponseInterface::class));
        $this->assertEquals('response', $this->container->getServiceIdFromAlias(ResponseInterface::class));
    }

    public function testGetRouter()
    {
        $this->assertInstanceOf(RouterInterface::class, $this->container->get('router'));
    }

    public function testGetRouterAlias()
    {
        $this->assertTrue($this->container->hasAlias(RouterInterface::class));
        $this->assertEquals('router', $this->container->getServiceIdFromAlias(RouterInterface::class));
    }

    public function testGetRouteDefinitionCallback()
    {
        $request = $this->createRequest('/admin/articles/10/my-story', 'GET');
        $this->container->set('request', $request);

        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => [
                'methods' => ['GET','POST'],
                'pattern' => '/foo',
                'middlewares' => ['midd1', 'midd2'],
                'handler' => ['_controller' => 'home','_action' => 'index'],
                'group' => 'adm'
            ]
        ]);
        $config->set('routes_groups', [
            'adm' => [
                'prefix' => '/admin',
                'middlewares' => ['adm_midd1', 'adm_midd2']
            ]
        ]);

        $router = $this->container->get('router');
        $result = $router->match($request);
        $this->assertInstanceOf('Tlumx\Router\Result', $result);
        $this->assertEquals('adm', $router->getRouteGroupName('foo'));
    }

    public function testInvalidGetRouteDefinitionCallbackNotRouteIsArray()
    {
        $request = $this->createRequest('/admin/articles/10/my-story', 'GET');
        $this->container->set('request', $request);
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => 'foo'
        ]);
        $router = $this->container->get('router');

        $this->expectException(InvalidRouterConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for route "%s": it must by in array.',
            'foo'
        ));

        $result = $router->match($request);
    }

    public function testInvalidGetRouteDefinitionCallbackNotIssetPattern()
    {
        $request = $this->createRequest('/admin/articles/10/my-story', 'GET');
        $this->container->set('request', $request);
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => []
        ]);
        $router = $this->container->get('router');

        $this->expectException(InvalidRouterConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for route "%s": not isset route pattern.',
            'foo'
        ));

        $result = $router->match($request);
    }

    public function testInvalidGetRouteDefinitionCallbackNotIssetHandler()
    {
        $request = $this->createRequest('/admin/articles/10/my-story', 'GET');
        $this->container->set('request', $request);
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => [
                'pattern' => '/foo',
            ]
        ]);
        $router = $this->container->get('router');

        $this->expectException(InvalidRouterConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for route "%s": not isset route handler.',
            'foo'
        ));

        $result = $router->match($request);
    }

    public function testGetRouteDefinitionCallbackWithOutGroup()
    {
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => [
                'pattern' => '/foo',
                'handler' => ['_controller' => 'home','_action' => 'index'],
            ]
        ]);
        $router = $this->container->get('router');
        $this->assertEquals('', $router->getRouteGroupName('foo'));
    }

    public function testInvalidGetRouteDefinitionCallbackNotGroupIsArray()
    {
        $config = $this->container->get('config');
        $config->set('routes', [
            'foo' => [
                'pattern' => '/foo',
                'handler' => ['_controller' => 'home','_action' => 'index'],
                'group' => 'adm'
            ]
        ]);
        $config->set('routes_groups', [
            'adm' => 'foo'
        ]);
        $router = $this->container->get('router');

        $this->expectException(InvalidRouterConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid configuration for route group "%s": it must by in array.',
            'adm'
        ));

        $routeDefinition = $router->getRouteGroupName('foo');
    }


    public function testGetEventManager()
    {
        $this->assertInstanceOf(EventManagerInterface::class, $this->container->get('event_manager'));
    }

    public function testGetEventManagerAlias()
    {
        $this->assertTrue($this->container->hasAlias(EventManagerInterface::class));
        $this->assertEquals('event_manager', $this->container->getServiceIdFromAlias(EventManagerInterface::class));
    }

    public function testEventManagerListeners()
    {
        $config = $this->container->get('config');
        $config->set('listeners', [
            'foo' => function ($e) {
                return 'foo-result';
            },
            'bar' => [ function ($e) {
                $e->setParams(['res' => 'bar-result-1']);
                return 'bar-result-1';
            }, function ($e) {
                $resArr = $e->getParams();
                $res = $resArr['res'].'-2';
                $e->setParams(['res' => $res]);
                $e->stopPropagation(true);
                return $res;
            }, function ($e) {
                return 'bar-result-3';
            } ]
        ]);
        $em = $this->container->get('event_manager');
        $this->assertEquals('foo-result', $em->trigger('foo'));
        $this->assertEquals('bar-result-1-2', $em->trigger('bar'));
    }

    public function testGetView()
    {
        $this->assertInstanceOf(ViewInterface::class, $this->container->get('view'));
    }

    public function testGetViewAlias()
    {
        $this->assertTrue($this->container->hasAlias(ViewInterface::class));
        $this->assertEquals('view', $this->container->getServiceIdFromAlias(ViewInterface::class));
    }

    public function testGetTemplatesManager()
    {
        $this->assertInstanceOf(TemplatesManager::class, $this->container->get('templates_manager'));
    }

    public function testGetTemplatesManagerAlias()
    {
        $this->assertTrue($this->container->hasAlias(TemplatesManager::class));
        $this->assertEquals('templates_manager', $this->container->getServiceIdFromAlias(TemplatesManager::class));
    }

    public function testGetTemplatesManagerPathsAndMap()
    {
        $config = $this->container->get('config');
        $config->set('templates_paths', [
            'a' => __DIR__,
            'b' => __DIR__
        ]);
        $config->set('templates', [
            'template1' => __FILE__,
            'template2' => __FILE__
        ]);
        $tm = $this->container->get('templates_manager');
        $this->assertEquals(rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $tm->getTemplatePath('a'));
        $this->assertEquals(rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $tm->getTemplatePath('b'));
        $this->assertEquals(__FILE__, $tm->getTemplate('template1'));
        $this->assertEquals(__FILE__, $tm->getTemplate('template2'));
    }

    public function testGetExceptionHandler()
    {
        $this->assertInstanceOf(ExceptionHandlerInterface::class, $this->container->get('exception_handler'));
    }

    public function testGetExceptionHandlerInterfaceAlias()
    {
        $this->assertTrue($this->container->hasAlias(ExceptionHandlerInterface::class));
        $actual = $this->container->getServiceIdFromAlias(ExceptionHandlerInterface::class);
        $this->assertEquals('exception_handler', $actual);
    }

    public function testGetNotFoundHandler()
    {
        $this->assertInstanceOf(NotFoundHandlerInterface::class, $this->container->get('not_found_handler'));
    }

    public function testGetNotFoundHandlerInterfaceAlias()
    {
        $this->assertTrue($this->container->hasAlias(NotFoundHandlerInterface::class));
        $actual = $this->container->getServiceIdFromAlias(NotFoundHandlerInterface::class);
        $this->assertEquals('not_found_handler', $actual);
    }

    public function testGetRouteMiddleware()
    {
        $this->assertInstanceOf(RouteMiddleware::class, $this->container->get('RouteMiddleware'));
    }

    public function testGetDispatchMiddleware()
    {
        $this->assertInstanceOf(DispatchMiddleware::class, $this->container->get('DispatchMiddleware'));
    }
}
