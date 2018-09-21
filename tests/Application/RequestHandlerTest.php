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

use Tlumx\Application\RequestHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Tlumx\Tests\Application\Fixtures\AMiddleware;
use Tlumx\Tests\Application\Fixtures\BMiddleware;
use Tlumx\Application\Exception\InvalidRequestHandlerResolveException;
use Tlumx\Application\Exception\InvalidMiddlewareException;

class RequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $requestMock = $this->prophesize(ServerRequestInterface::class)->reveal();
        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();
        $containerMock = $this->prophesize(ContainerInterface::class)->reveal();
        $handler = new RequestHandler([], function () use ($responseMock) {
            return $responseMock;
        }, $containerMock);

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    public function testDefaultCallable()
    {
        $requestMock = $this->prophesize(ServerRequestInterface::class)->reveal();
        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();
        $containerMock = $this->prophesize(ContainerInterface::class)->reveal();
        $handler = new RequestHandler([], function () use ($responseMock) {
            return $responseMock;
        }, $containerMock);

        $this->assertSame($responseMock, $handler->handle($requestMock));
    }

    public function testSuccess()
    {
        // Requests for each middlewares & DefaultCallable
        $requestForDefaultCallableMock = $this->prophesize(ServerRequestInterface::class)->reveal();

        $requestForBMiddlewareProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestForBMiddlewareProphecy->getAttribute('msg')->shouldBeCalled(1)->willReturn('from_a_midds');
        $requestForBMiddlewareProphecy->withAttribute('msg', 'from_b_midds')
                                      ->shouldBeCalled(1)
                                      ->willReturn($requestForDefaultCallableMock);

        $requestForAMiddlewareProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestForAMiddlewareProphecy->getAttribute('msg')->shouldBeCalled(1)->willReturn(null);
        $requestForAMiddlewareProphecy->withAttribute('msg', 'from_a_midds')
                                      ->shouldBeCalled(1)
                                      ->willReturn($requestForBMiddlewareProphecy
                                      ->reveal());
        $requestForAMiddlewareMock = $requestForAMiddlewareProphecy->reveal();

        // Setup Container
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('a')->shouldBeCalled(1)->willReturn(true);
        $containerProphecy->get('a')->shouldBeCalled(1)->willReturn(new AMiddleware());
        $containerProphecy->has('b')->shouldBeCalled(1)->willReturn(true);
        $containerProphecy->get('b')->shouldBeCalled(1)->willReturn(new BMiddleware());
        $containerMock = $containerProphecy->reveal();

        // TRY
        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();
        $handler = new RequestHandler(
            ['a', 'b'],
            function ($request) use ($responseMock, $requestForDefaultCallableMock) {
                // Check if passed request and request from DMiddleware are same
                $this->assertSame($request, $requestForDefaultCallableMock);
                return $responseMock;
            },
            $containerMock
        );
        $handler->handle($requestForAMiddlewareMock);
    }

    public function testInvalidResolveMiddlevareNotFound()
    {
        $requestMock = $this->prophesize(ServerRequestInterface::class)->reveal();
        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('a')->shouldBeCalled(1)->willReturn(false);
        $containerMock = $containerProphecy->reveal();

        $handler = new RequestHandler(['a'], function () use ($responseMock) {
            return $responseMock;
        }, $containerMock);

        $this->expectException(InvalidRequestHandlerResolveException::class);
        $this->expectExceptionMessage(sprintf(
            "Unable to resolve middleware \"%s\", it is not passed in the Container.",
            'a'
        ));

        $handler->handle($requestMock);
    }

    public function testInvalidResolveNotMiddlewareInterface()
    {
        $requestMock = $this->prophesize(ServerRequestInterface::class)->reveal();
        $responseMock = $this->prophesize(ResponseInterface::class)->reveal();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('a')->shouldBeCalled(1)->willReturn(true);
        $containerProphecy->get('a')->shouldBeCalled(1)->willReturn('Not_MiddlewareInterface');
        $containerMock = $containerProphecy->reveal();

        $handler = new RequestHandler(['a'], function () use ($responseMock) {
            return $responseMock;
        }, $containerMock);

        $this->expectException(InvalidMiddlewareException::class);
        $this->expectExceptionMessage(sprintf(
            "The middleware \"%s\" is not instanceof \"%s\".",
            'a',
            MiddlewareInterface::class
        ));

        $handler->handle($requestMock);
    }
}
