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

/**
 * Default routing middleware.
 */
class RouteMiddleware implements MiddlewareInterface
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
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $em = $this->container->get('event_manager');
        $event = new ApplicationEvent(ApplicationEvent::EVENT_PRE_ROUTING);
        $event->setContainer($this->container);
        $em->trigger($event);

        $router = $this->container->get('router');
        $result = $router->match($request);

        $request = $request->withAttribute(Result::class, $result);

        if ($result->isSuccess()) {
            foreach ($result->getParams() as $param => $value) {
                $request = $request->withAttribute($param, $value);
            }
        }

        $event = new ApplicationEvent(ApplicationEvent::EVENT_POST_ROUTING);
        $event->setContainer($this->container);
        $em->trigger($event);

        return $handler->handle($request);
    }
}
