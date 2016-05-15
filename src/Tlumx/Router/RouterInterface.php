<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Router;

/**
 * Router interface.
 */
interface RouterInterface 
{
    /**
     * Set base path
     *
     * @param string $basePath
     */
    public function setBasePath($basePath);

    /**
     * Set routes
     *
     * @param array $routes
     * @param string|null $parent
     */
    public function setRoutes(array $routes, $parent = null);

    /**
     * Set route
     *
     * @param string $name
     * @param array|string $methods
     * @param string $route
     * @param array $handler
     * @param array $filters
     * @param array $middlewares
     * @param string|null $parent
     */
    public function setRoute($name,
                            $methods,
                            $route,
                            array $handler,
                            array $filters = [],
                            array $middlewares=[],
                            $parent = null);

    /**
     * Add middleware
     *
     * @param string $routeName
     * @param array $middlewares
     */
    public function addMiddleware($routeName, array $middlewares);

    /**
     * Router match
     *
     * @param string $method
     * @param string $urlPath
     */
    public function match($method, $urlPath);

    /**
     * Create route path
     *
     * @param string $name
     * @param array $params
     * @param array $query
     */
    public function createPath($name, array $params = [], array $query = []);
}