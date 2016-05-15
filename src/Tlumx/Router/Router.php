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
 * Router interface implementation.
 */
class Router implements RouterInterface
{
    /**
     * REGVAL
     */
    const REGVAL = '/{([\w-]+)}/';

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Set base path
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = (string) $basePath;
    }

    /**
     * Set routes
     *
     * @param array $routes
     * @param string|null $parent
     */
    public function setRoutes(array $routes, $parent = null)
    {
        foreach ($routes as $name => $options) {
            
            if(!is_array($options)) {
                throw new \InvalidArgumentException('Route options must be an array');
            }
                                                            
            if(!isset($options['methods']) || !isset($options['route']) || !isset($options['handler'])) {
                throw new \InvalidArgumentException('Invalid route options');
            }
        
            $methods = is_array($options['methods']) ? $options['methods'] : array($options['methods']);
        
            $filters = (isset($options['filters']) && is_array($options['filters'])) ? $options['filters'] : [];
            
            $middlewares = (isset($options['middlewares']) && is_array($options['middlewares'])) ? 
                    $options['middlewares'] : [];            
            
            $this->setRoute($name, $methods, $options['route'], $options['handler'], $filters, $middlewares, $parent);
            
            if(isset($options['child_routes'])) {
                if(!is_array($options['child_routes'])) {
                    throw new \InvalidArgumentException('Route option "child_routes" must be an array');
                }
                $this->setRoutes($options['child_routes'], $name);
            }
        }
    }

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
                            $parent = null)
    {
        if(isset($this->routes[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Route "%s" is alredy defined', $name
            ));
        }
        
        if(!is_array($methods)) {
            $methods = array($methods);
        }
        
        if(!is_string($route) || empty($route)) {
            throw new \InvalidArgumentException('Route pattern must be a not empty string');
        }
        
        $routeRegex = $this->createRouteRegex($route, $filters);
        
        if($parent !== null) {
            if(!isset($this->routes[$parent])) {
                throw new \InvalidArgumentException(sprintf(
                    'Parent route "%s" is not defined', $parent
                ));
            }
            
            $routeRegex = $this->routes[$parent]['route_regex'].$routeRegex;
            $route = $this->routes[$parent]['route'].$route;
            
            $middlewares = array_merge($this->routes[$parent]['middlewares'], $middlewares);
        }
        
        $this->routes[$name] = [
            'methods' => $methods,
            'route' => $route,
            'route_regex' => $routeRegex,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'parent' => $parent
        ];
    }

    /**
     * Create route regex from pattern
     *
     * @param string $pattern
     * @param array $filters
     * @return string
     */
    protected function createRouteRegex($pattern, array $filters = [])
    {
        return preg_replace_callback(self::REGVAL, function ($matches) use($filters) {
            if (isset($matches[1]) && isset($filters[$matches[1]])) {
                return $filters[$matches[1]];
            }
            return "([\w-]+)";
        }, $pattern);
    }

    /**
     * Add middleware
     *
     * @param string $routeName
     * @param array $middlewares
     */
    public function addMiddleware($routeName, array $middlewares)
    {
        if(!isset($this->routes[$routeName])) {
            throw new \InvalidArgumentException(sprintf(
                'Route "%s" is not defined', $routeName
            ));
        }
        
        $midds = array_merge($this->routes[$routeName]['middlewares'], $middlewares);
        $this->routes[$routeName]['middlewares'] = $midds;
        
        
        foreach ($this->routes as $name => $route) {
            if($route['parent'] !== $routeName) {
                continue;
            }
            
            $midds = array_merge($this->routes[$name]['middlewares'], $middlewares);
            $this->routes[$name]['middlewares'] = $midds;
        }        
    }

    /**
     * Router match
     *
     * @param string $method
     * @param string $urlPath
     */
    public function match($method, $urlPath)
    {
        $basePath = '/' . trim($this->basePath, '/');
        if($basePath == '/') {
            $basePath = '';
        }
        
        $allowMethods = [];
        
        foreach ($this->routes as $routeName => $route) {
            $regex = $basePath . $route['route_regex'];
            if(!preg_match('#^'.$regex.'$#', $urlPath, $matches)) {
                continue;
            }
            
            if(!in_array($method, $route['methods'])) {
                $allowMethods = $route['methods'];
                $routeNameAllowMethods = $routeName;
                continue;
            }
            
            $params = [];
            
            if (preg_match_all(self::REGVAL, $route['route'], $keys)) {
                foreach ($keys[1] as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $params[$name] = $matches[$key + 1];
                    }
                }
            }
            
            return Result::createSuccessful($routeName, $route['handler'], $params, $route['middlewares']);
        }
        
        return empty($allowMethods) ? Result::createFailure() :
            Result::createFailureMethod($routeNameAllowMethods, $allowMethods);
    }

    /**
     * Create route path
     *
     * @param string $name
     * @param array $params
     * @param array $query
     */
    public function createPath($name, array $params = [], array $query = [])
    {
        if(!isset($this->routes[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Route "%s" is not isset', $name
            ));
        }        
        
        $urlPath = $this->routes[$name]['route'];        
        if (preg_match_all(self::REGVAL, $urlPath, $matches)) {
            $keys = $matches[1];
            foreach ($keys as $index => $key) {
                if (isset($params[$key])) {
                    $urlPath = preg_replace(self::REGVAL, $params[$key], $urlPath, 1);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'Route param "%s" is not isset', $key
                    ));
                }
            }
        }
        
        if (!empty($query)) {
            $urlPath .= '?' . http_build_query($query);
        }              
        
        $basePath = '/' . trim($this->basePath, '/');
        if($basePath == '/') {
            $basePath = '';
        }
        
        return $basePath.$urlPath;
    }    
}