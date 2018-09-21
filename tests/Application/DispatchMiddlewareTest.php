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
use Tlumx\Application\Middleware\DispatchMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tlumx\Router\Result;
use Tlumx\EventManager\EventManagerInterface;
use Tlumx\Application\Handler\NotFoundHandlerInterface;
use Tlumx\Application\Exception\RouterResultNotFoundException;
use Tlumx\Application\Exception\ControllerNotFoundException;
use Tlumx\Application\Exception\InvalidControllerException;

class DispatchMiddlewareTest extends \PHPUnit\Framework\TestCase
{
    public function createRequest(string $uriPath = '/', string $method = 'GET')
    {
        $request = $this->prophesize(ServerRequestInterface::class);

        return $request;
    }

    public function createResponse()
    {
        return $this->prophesize(ResponseInterface::class);
    }

    public function createContainer()
    {
        return $this->prophesize(ContainerInterface::class);
    }

    public function createRequestHandler()
    {
        return $this->prophesize(RequestHandlerInterface::class);
    }

    public function createNotFoundHandler()
    {
        return $this->prophesize(NotFoundHandlerInterface::class);
    }

    public function createResultMethodNotAllowed()
    {
        $result = $this->prophesize(Result::class);
        $result->isMethodNotAllowed()->willReturn(true);

        return $result;
    }

    public function createResultNotFound()
    {
        $result = $this->prophesize(Result::class);
        $result->isMethodNotAllowed()->willReturn(false);
        $result->isNotFound()->willReturn(true);

        return $result;
    }

    public function createResultSuccess(array $middelwares = [])
    {
        $result = $this->prophesize(Result::class);
        $result->isMethodNotAllowed()->willReturn(false);
        $result->isNotFound()->willReturn(false);
        $result->getRouteMiddlewares()->willReturn($middelwares);
        $result->getRouteHandler()->willReturn([]);

        return $result;
    }

    public function createMiddleware()
    {
        $midd = $this->prophesize(MiddlewareInterface::class);
        return $midd;
    }

    public function createEventManager()
    {
        $em = $this->prophesize(EventManagerInterface::class);
        $em->trigger(\Prophecy\Argument::any())->shouldBeCalledTimes(2);
        return $em;
    }

    public function testImplements()
    {
        $middleware = new DispatchMiddleware($this->createContainer()->reveal());
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testResultNotInRequest()
    {
        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn(false);

        $requestHandler = $this->createRequestHandler();
        $requestHandler->handle($request->reveal())->willReturn($this->createResponse()->reveal());


        $this->expectException(RouterResultNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            "Route Result attribut \"%s\" is not found in request",
            Result::class
        ));

        $middleware = new DispatchMiddleware($this->createContainer()->reveal());
        $response = $middleware->process($request->reveal(), $requestHandler->reveal());
    }

    public function testResultMethodNotAllowed()
    {
        $result = $this->createResultMethodNotAllowed();
        $result->getAllowedMethods()->willReturn(['GET', 'POST']);

        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());

        $response = $this->createResponse();

        $NotFoundHandler = $this->createNotFoundHandler();
        $NotFoundHandler->handle($result->reveal()->getAllowedMethods())->willReturn($response->reveal());

        $container = $this->createContainer();
        $container->get('not_found_handler')->willReturn($NotFoundHandler->reveal());

        $requestHandler = $this->createRequestHandler();

        $middleware = new DispatchMiddleware($container->reveal());
        $getResponse = $middleware->process($request->reveal(), $requestHandler->reveal());
        $this->assertSame($response->reveal(), $getResponse);
    }

    public function testResultNotFound()
    {
        $result = $this->createResultNotFound();

        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());

        $response = $this->createResponse();

        $NotFoundHandler = $this->createNotFoundHandler();
        $NotFoundHandler->handle()->willReturn($response->reveal());

        $container = $this->createContainer();
        $container->get('not_found_handler')->willReturn($NotFoundHandler->reveal());

        $requestHandler = $this->createRequestHandler();

        $middleware = new DispatchMiddleware($container->reveal());
        $getResponse = $middleware->process($request->reveal(), $requestHandler->reveal());
        $this->assertSame($response->reveal(), $getResponse);
    }

    public function testResultSuccessNotUseController()
    {

        $response = $this->createResponse();

        $result = $this->createResultSuccess(['Midd1']);

        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());

        $requestHandler = $this->createRequestHandler();

        $midd1 = $this->createMiddleware();
        $midd1->process($request->reveal(), \Prophecy\Argument::any())->willReturn($response->reveal());
        $container = $this->createContainer();
        $container->has('Midd1')->willReturn(true);
        $container->get('Midd1')->willReturn($midd1->reveal());

        $middleware = new DispatchMiddleware($container->reveal());
        $getResponse = $middleware->process($request->reveal(), $requestHandler->reveal());
        $this->assertSame($response->reveal(), $getResponse);
    }

    public function testDispatchNotFoundResult()
    {
        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn(false);
        $container = $this->createContainer();
        $middleware = new DispatchMiddleware($container->reveal());

        $this->expectException(RouterResultNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            "Route Result attribut \"%s\" is not found in request",
            Result::class
        ));

        $middleware->dispatch($request->reveal());
    }

    public function testDispatchNotFoundController()
    {
        $result = $this->createResultSuccess();
        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());
        $container = $this->createContainer();
        $middleware = new DispatchMiddleware($container->reveal());

        $this->expectException(ControllerNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            "Controller with name \"%s\" is not exist",
            'index'
        ));

        $middleware->dispatch($request->reveal());
    }

    public function testDispatchInvalidController()
    {
        $result = $this->createResultSuccess();
        $result->getRouteHandler()->willReturn(['controller' => 'foo']);
        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());
        $container = $this->createContainer();
        $container->has('foo')->willReturn(true);
        $container->get('foo')->willReturn('this is not controller');
        $middleware = new DispatchMiddleware($container->reveal());

        $this->expectException(InvalidControllerException::class);
        $this->expectExceptionMessage(sprintf(
            "Controller with name \"%s\" is not instance of Psr\Http\Server\RequestHandlerInterface",
            'foo'
        ));

        $middleware->dispatch($request->reveal());
    }

    public function testDispatchController()
    {
        $result = $this->createResultSuccess();
        $result->getRouteHandler()->willReturn(['controller' => 'foo']);

        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());

        $response = $this->createResponse();

        $controllerHandler = $this->createRequestHandler();
        $controllerHandler->handle($request->reveal())->willReturn($response->reveal());

        $em = $this->createEventManager();

        $container = $this->createContainer();
        $container->has('foo')->willReturn(true);
        $container->get('foo')->willReturn($controllerHandler->reveal());
        $container->get('event_manager')->willReturn($em->reveal());

        $middleware = new DispatchMiddleware($container->reveal());
        $getResponse = $middleware->dispatch($request->reveal());
        $this->assertSame($response->reveal(), $getResponse);
    }

    public function testResultSuccessUseController()
    {
        $response = $this->createResponse();

        $result = $this->createResultSuccess();
        $result->getRouteHandler()->willReturn(['controller' => 'foo']);

        $request = $this->createRequest();
        $request->getAttribute(Result::class, false)->willReturn($result->reveal());

        $requestHandler = $this->createRequestHandler();

        $controllerHandler = $this->createRequestHandler();
        $controllerHandler->handle($request->reveal())->willReturn($response->reveal());

        $em = $this->createEventManager();

        $container = $this->createContainer();
        $container->has('foo')->willReturn(true);
        $container->get('foo')->willReturn($controllerHandler->reveal());
        $container->get('event_manager')->willReturn($em->reveal());

        $middleware = new DispatchMiddleware($container->reveal());
        $getResponse = $middleware->process($request->reveal(), $requestHandler->reveal());
        $this->assertSame($response->reveal(), $getResponse);
    }
}
