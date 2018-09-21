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

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tlumx\Router\RouterInterface;
use Tlumx\Router\Result;
use Tlumx\Application\Middleware\RouteMiddleware;
use Tlumx\EventManager\EventManagerInterface;

class RouteMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    protected $request;

    protected $response;

    protected $container;

    protected $middleware;

    protected $handler;

    protected $router;

    public function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class);
        $this->router     = $this->prophesize(RouterInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->em = $this->prophesize(EventManagerInterface::class);

        $this->middleware = new RouteMiddleware($this->container->reveal());
    }

    public function testImplements()
    {
        $this->assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    public function testRoutingSuccess()
    {
        $this->handler->handle($this->request->reveal())->will([$this->response, 'reveal']);
        $this->container->get('router')->willReturn($this->router);
        $this->container->get('event_manager')->willReturn($this->em);

        $params = ['a' => 'b', 'c' => 'd'];
        $result = Result::createSuccess(
            'foo',
            $params
        );

        $this->router->match($this->request->reveal())->willReturn($result);

        $this->request->withAttribute(Result::class, $result)->will([$this->request, 'reveal']);
        foreach ($params as $key => $value) {
            $this->request->withAttribute($key, $value)->will([$this->request, 'reveal']);
        }

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($this->response->reveal(), $response);
    }
}
