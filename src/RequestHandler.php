<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Application;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

/**
* PSR-15 Psr\Http\Server\RequestHandlerInterface wrapper
*/
class RequestHandler implements RequestHandlerInterface
{
    /**
    * @var string[] MiddlewareInterface name services
    */
    private $middlewares;

    /**
    * @var callable
    */
    private $default;

    /**
    * @var ContainerInterface
    */
    private $container;

    /**
    * Constructor.
    *
    * @param array $middlewares
    * @param callable $default
    * @param ContainerInterface $container
    */
    public function __construct(array $middlewares, callable $default, ContainerInterface $container)
    {
        $this->middlewares = $middlewares;
        $this->default = $default;
        $this->container = $container;
    }

    /**
    * {@inheritdoc}
    */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = array_shift($this->middlewares);

        if ($name === null) {
            return call_user_func($this->default, $request);
        }

        $middleware = $this->resolve($name);

        return $middleware->process($request, clone $this);
    }

    /**
    * Resolve middleware from Container.
    *
    * @param string $name middleware name in Container
    *
    * @return MiddlewareInterface
    * @throws \LogicException
    */
    private function resolve($name): MiddlewareInterface
    {
        if (!$this->container->has($name)) {
            throw new \LogicException(
                sprintf("Unable to resolve middleware \"%s\", it is not passed in the Container.", $name)
            );
        }

        $middleware = $this->container->get($name);

        if (!($middleware instanceof MiddlewareInterface)) {
            throw new \LogicException(
                sprintf(
                    "The middleware \"%s\" is not instanceof \"%s\".",
                    $name,
                    MiddlewareInterface::class
                )
            );
        }

        return $middleware;
    }
}
