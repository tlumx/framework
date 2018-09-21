<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Application\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tlumx\Router\Result;
use Tlumx\Application\ApplicationEvent;
use Tlumx\Application\RequestHandler;
use Tlumx\Application\Exception\RouterResultNotFoundException;
use Tlumx\Application\Exception\ControllerNotFoundException;
use Tlumx\Application\Exception\InvalidControllerException;

/**
 * Default dispatch middleware.
 */
class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @throws RouterResultNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $result = $request->getAttribute(Result::class, false);
        if (! $result) {
            throw new RouterResultNotFoundException(sprintf(
                "Route Result attribut \"%s\" is not found in request",
                Result::class
            ));
        }

        if ($result->isMethodNotAllowed()) {
            $notFoundHandler = $this->container->get('not_found_handler');
            return $notFoundHandler->handle($result->getAllowedMethods());
        } elseif ($result->isNotFound()) {
            $notFoundHandler = $this->container->get('not_found_handler');
            return $notFoundHandler->handle();
        }

        $handler = new RequestHandler($result->getRouteMiddlewares(), [$this, 'dispatch'], $this->container);
        return $handler->handle($request);
    }

    /**
     * Default dispatching.
     *
     * @throws RouterResultNotFoundException
     * @throws ControllerNotFoundException
     * @throws InvalidControllerException
     */
    public function dispatch($request)
    {
        $result = $request->getAttribute(Result::class, false);
        if (! $result) {
            throw new RouterResultNotFoundException(sprintf(
                "Route Result attribut \"%s\" is not found in request",
                Result::class
            ));
        }

        $routeHandler = $result->getRouteHandler();
        $controllerName = isset($routeHandler['controller']) ? $routeHandler['controller'] : 'index';
        if (!$this->container->has($controllerName)) {
            throw new ControllerNotFoundException(sprintf(
                "Controller with name \"%s\" is not exist",
                $controllerName
            ));
        }

        $controller = $this->container->get($controllerName);
        if (!($controller instanceof RequestHandlerInterface)) {
            throw new InvalidControllerException(sprintf(
                "Controller with name \"%s\" is not instance of Psr\Http\Server\RequestHandlerInterface",
                $controllerName
            ));
        }

        $em = $this->container->get('event_manager');
        $event = new ApplicationEvent(ApplicationEvent::EVENT_PRE_DISPATCH);
        $event->setContainer($this->container);
        $em->trigger($event);

        $response = $controller->handle($request);

        $event = new ApplicationEvent(ApplicationEvent::EVENT_POST_DISPATCH);
        $event->setContainer($this->container);
        $em->trigger($event);

        return $response;
    }
}
