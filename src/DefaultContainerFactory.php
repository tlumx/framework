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

use Psr\Container\ContainerInterface;
use Tlumx\ServiceContainer\ServiceContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;
use Tlumx\Router\RouterInterface;
use Tlumx\Router\Router;
use Tlumx\Router\RouteCollector;
use Tlumx\EventManager\EventManagerInterface;
use Tlumx\EventManager\EventManager;
use Tlumx\View\ViewInterface;
use Tlumx\View\View;
use Tlumx\View\TemplatesManager;
use Tlumx\Application\Handler\ExceptionHandlerInterface;
use Tlumx\Application\Handler\DefaultExceptionHandler;
use Tlumx\Application\Handler\NotFoundHandlerInterface;
use Tlumx\Application\Handler\DefaultNotFoundHandler;
use Tlumx\Application\Middleware\RouteMiddleware;
use Tlumx\Application\Middleware\DispatchMiddleware;

/**
 * Class for creation and configuration Psr Container (Tlumx\ServiceContaine\ServiceContainer).
 */
class DefaultContainerFactory
{
    /**
     * Create Psr\Container\ContainerInterface and configure it
     * (Tlumx ServiceContainer)
     *
     * @param array $config
     * @param ConfigureContainerInterface $configurationObj
     * @return ContainerInterface
     */
    public function create(array $config, ConfigureContainerInterface $configureObj = null) : ContainerInterface
    {
        $container = new ServiceContainer();

        $configObj = new Config($config);
        $container->set('config', $configObj);

        if ($configureObj !== null) {
            $configureObj->configureContainer($container, $config);
        }

        $this->addRequest($container);
        $this->addResponse($container);
        $this->addRouter($container);
        $this->addEventManager($container);
        $this->addView($container);
        $this->addTemplatesManager($container);
        $this->addExceptionHandler($container);
        $this->addNotFoundHandler($container);
        $this->addRouteMiddleware($container);
        $this->addDispatchMiddleware($container);

        return $container;
    }

    /**
     * Add 'request' service (if it not exist) and set 'ServerRequestInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addRequest(ContainerInterface $container)
    {
        if (!$container->has('request')) {
            $container->register('request', function () {
                return ServerRequestFactory::fromGlobals();
            });
        }

        if (!$container->hasAlias(ServerRequestInterface::class)) {
            $container->setAlias(ServerRequestInterface::class, 'request');
        }
    }

    /**
     * Add 'response' service (if it not exist) and set 'ResponseInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addResponse(ContainerInterface $container)
    {
        if (!$container->has('response')) {
            $container->register('response', function () {
                return new Response();
            });
        }

        if (!$container->hasAlias(ResponseInterface::class)) {
            $container->setAlias(ResponseInterface::class, 'response');
        }
    }

    /**
     * Add 'router' service (if it not exist) and set 'RouterInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addRouter(ContainerInterface $container)
    {
        if (!$container->has('router')) {
            $this->getRouteDefinitionCallback($container);
            $container->register('router', function ($container) {
                $routeDefinitionCallback = $container->get('route_definition_callback');
                $configObj = $container->get('config');
                $cacheEnabled = (bool) $configObj->get('router_cache_enabled');
                $cacheFile = (string) $configObj->get('router_cache_file');
                return new Router($routeDefinitionCallback, $cacheEnabled, $cacheFile);
            });
        }

        if (!$container->hasAlias(RouterInterface::class)) {
            $container->setAlias(RouterInterface::class, 'router');
        }
    }

    /**
     * Add 'route_definition_callback' service (use in 'router' service)
     *
     * @param ContainerInterface $container
     * @throws Exception\InvalidRouterConfigurationException
     */
    protected function getRouteDefinitionCallback(ContainerInterface $container)
    {
        $container->register('route_definition_callback', function ($container) {
            $configObj = $container->get('config');
            $routes = $configObj->get('routes', []);
            $groups = $configObj->get('routes_groups', []);

            return function (RouteCollector $r) use ($routes, $groups) {
                foreach ($routes as $name => $route) {
                    if (!is_array($route)) {
                        throw new Exception\InvalidRouterConfigurationException(sprintf(
                            'Invalid configuration for route "%s": it must by in array.',
                            $name
                        ));
                    }

                    $methods = isset($route['methods']) ? (array) $route['methods'] : ['GET'];

                    if (!isset($route['pattern'])) {
                        throw new Exception\InvalidRouterConfigurationException(sprintf(
                            'Invalid configuration for route "%s": not isset route pattern.',
                            $name
                        ));
                    }

                    $middlewares = (!isset($route['middlewares'])) ? [] : (array) $route['middlewares'];

                    if (!isset($route['handler'])) {
                        throw new Exception\InvalidRouterConfigurationException(sprintf(
                            'Invalid configuration for route "%s": not isset route handler.',
                            $name
                        ));
                    }

                    if (isset($route['group']) && is_string($route['group'])) {
                        $group = $route['group'];
                    } else {
                        $group = '';
                    }

                    $r->addRoute(
                        (string) $name,
                        $methods,
                        ((string) $route['pattern']),
                        $middlewares,
                        (array) $route['handler'],
                        $group
                    );
                }

                foreach ($groups as $name => $group) {
                    if (!is_array($group)) {
                        throw new Exception\InvalidRouterConfigurationException(sprintf(
                            'Invalid configuration for route group "%s": it must by in array.',
                            $name
                        ));
                    }

                    $prefix = isset($group['prefix']) ? $group['prefix'] : '';

                    $middlewares = (!isset($route['middlewares'])) ? [] : (array) $route['middlewares'];

                    $r->addGroup(
                        (string) $name,
                        (string) $prefix,
                        $middlewares
                    );
                }
            };
        });
    }

    /**
     * Add 'event_manager' service (if it not exist) and set 'EventManagerInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addEventManager(ContainerInterface $container)
    {
        if (!$container->has('event_manager')) {
            $container->register('event_manager', function ($container) {
                $eventManager = new EventManager();
                $configObj = $container->get('config');
                $listeners = $configObj->get('listeners');

                if (is_array($listeners)) {
                    foreach ($listeners as $name => $listener) {
                        if (is_array($listener)) {
                            foreach ($listener as $l) {
                                $eventManager->attach($name, $l);
                            }
                        } else {
                            $eventManager->attach($name, $listener);
                        }
                    }
                }
                return $eventManager;
            });
        }

        if (!$container->hasAlias(EventManagerInterface::class)) {
            $container->setAlias(EventManagerInterface::class, 'event_manager');
        }
    }

    /**
     * Add 'view' service (if it not exist) and set 'ViewInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addView(ContainerInterface $container)
    {
        if (!$container->has('view')) {
            $container->register('view', function () {
                return new View();
            });
        }

        if (!$container->hasAlias(ViewInterface::class)) {
            $container->setAlias(ViewInterface::class, 'view');
        }
    }

    /**
     * Add 'templates_manager' service (if it not exist) and set 'TemplatesManager::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addTemplatesManager(ContainerInterface $container)
    {
        if (!$container->has('templates_manager')) {
            $container->register('templates_manager', function ($container) {
                $configObj = $container->get('config');
                $templatesManager = new TemplatesManager();
                $templatesPaths = $configObj->get('templates_paths', []);
                if ($templatesPaths) {
                    $templatesManager->setTemplatePaths($templatesPaths);
                }
                $templates = $configObj->get('templates', []);
                if ($templates) {
                    $templatesManager->setTemplateMap($templates);
                }
                return $templatesManager;
            });
        }

        if (!$container->hasAlias(TemplatesManager::class)) {
            $container->setAlias(TemplatesManager::class, 'templates_manager');
        }
    }

    /**
     * Add 'exception_handler' service (if it not exist) and set 'ExceptionHandlerInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addExceptionHandler(ContainerInterface $container)
    {
        if (!$container->has('exception_handler')) {
            $container->register('exception_handler', function ($container) {
                return new DefaultExceptionHandler($container);
            });
        }

        if (!$container->hasAlias(ExceptionHandlerInterface::class)) {
            $container->setAlias(ExceptionHandlerInterface::class, 'exception_handler');
        }
    }

    /**
     * Add 'not_found_handler' service (if it not exist) and set 'NotFoundHandlerInterface::class' alias
     *
     * @param ContainerInterface $container
     */
    protected function addNotFoundHandler(ContainerInterface $container)
    {
        if (!$container->has('not_found_handler')) {
            $container->register('not_found_handler', function ($container) {
                return new DefaultNotFoundHandler($container);
            });
        }

        if (!$container->hasAlias(NotFoundHandlerInterface::class)) {
            $container->setAlias(NotFoundHandlerInterface::class, 'not_found_handler');
        }
    }

    /**
     * Add 'RouteMiddleware' service (if it not exist)
     *
     * @param ContainerInterface $container
     */
    protected function addRouteMiddleware(ContainerInterface $container)
    {
        if (!$container->has('RouteMiddleware')) {
            $container->register('RouteMiddleware', function ($container) {
                return new RouteMiddleware($container);
            });
        }
    }

    /**
     * Add 'DispatchMiddleware' service (if it not exist)
     *
     * @param ContainerInterface $container
     */
    protected function addDispatchMiddleware(ContainerInterface $container)
    {
        if (!$container->has('DispatchMiddleware')) {
            $container->register('DispatchMiddleware', function ($container) {
                return new DispatchMiddleware($container);
            });
        }
    }
}
