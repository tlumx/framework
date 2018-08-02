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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tlumx\EventManager\Event;
use Tlumx\Router\Result as RouterResult;

/**
 * Tlumx Application class.
 */
class Application
{
    /**
     * Current framework version
     */
    const VERSION = '1.0';

    const EVENT_POST_BOOTSTRAP = 'event.post.bootstrap';
    const EVENT_PRE_ROUTING = 'event.pre.routing';
    const EVENT_POST_ROUTING = 'event.post.routing';
    const EVENT_PRE_DISPATCH = 'event.pre.dispatch';
    const EVENT_POST_DISPATCH = 'event.post.dispatch';

    /**
     * @var \Tlumx\ServiceProvider
     */
    protected $serviceProvider;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var bool
     */
    protected $middlewareCanAdd = true;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->serviceProvider = new ServiceProvider($config);
    }

    /**
     * Get config
     *
     * @param string|null $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $this->serviceProvider->getConfig($option);
    }

    /**
     * Set config
     *
     * @param mixed $option
     * @param mixed $value
     */
    public function setConfig($option, $value = null)
    {
        $this->serviceProvider->setConfig($option, $value);
    }

    /**
     * @return \Tlumx\ServiceProvider
     */
    public function getServiceProvider()
    {
        return $this->serviceProvider;
    }

    /**
     * Add middleware
     *
     * @param mixed $middleware
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function add($middleware)
    {
        if (!$this->middlewareCanAdd) {
            throw new \RuntimeException('Middleware can’t be added');
        }

        if (is_string($middleware) && $this->getServiceProvider()->has($middleware)) {
            $middleware = $this->getMiddlewareFromService($middleware);
        }

        if (!is_callable($middleware)) {
            throw new \InvalidArgumentException('Middleware is not callable');
        }

        $this->middlewares[] = $middleware;
    }

    /**
     * Get middleware from service provider as service
     *
     * @param string $service
     * @return callable
     */
    private function getMiddlewareFromService($service)
    {
        return function ($request, $response, $next) use ($service) {
            $mv = $this->getServiceProvider()->get($service);
            if (!is_callable($mv)) {
                throw new \RuntimeException(sprintf(
                    'Middleware "%s" is not invokable',
                    $service
                ));
            }
            return $mv($request, $response, $next);
        };
    }

    /**
     * Create \ErrorExeption
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws \ErrorException
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() == 0) {
            return;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Run the list of application  bootstrapper
     *
     * @throws Exception\BootstrapperClassNotFoundException
     * @throws Exception\InvalidBootstrapperClassException
     */
    protected function bootstrap()
    {
        $bootstrappers = $this->getConfig('bootstrappers');

        if (!is_array($bootstrappers)) {
            return;
        }

        foreach ($bootstrappers as $k => $class) {
            $class = (string) $class;
            if (!class_exists($class)) {
                throw new Exception\BootstrapperClassNotFoundException(
                    sprintf('Bootstrapper class "%s" not found', $class)
                );
            }

            $r = new \ReflectionClass($class);
            if (!$r->isSubclassOf('Tlumx\Bootstrapper')) {
                throw new Exception\InvalidBootstrapperClassException(
                    sprintf("Bootstrapper class \"%s\" must extend from Tlumx\\Bootstrapper", $class)
                );
            }

            $bootstrap = new $class($this);
        }
    }

    /**
     * Run
     *
     * @param bool $sendResponse
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function run($sendResponse = true)
    {
        error_reporting($this->getConfig('error_reporting'));
        ini_set('display_errors', $this->getConfig('display_errors'));
        set_error_handler([$this, 'errorHandler']);

        try {
            $em = $this->serviceProvider->getEventManager();
            $event = new Event('', null, ['application' => $this]);
            $this->bootstrap();
            $event->setName(self::EVENT_POST_BOOTSTRAP);
            $em->trigger($event);

            $this->middlewares[] = [$this, 'dispatchRouter'];

            $next = function ($request, $response) use (&$next) {
                if (empty($this->middlewares)) {
                    return $response;
                }

                $middleware = array_shift($this->middlewares);

                $result = call_user_func($middleware, $request, $response, $next);
                if ($result instanceof ResponseInterface === false) {
                    throw new \RuntimeException(
                        'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                    );
                }
                return $result;
            };

            $this->middlewareCanAdd = false;
            $response = $next($this->serviceProvider->getRequest(), $this->serviceProvider->getResponse());
        } catch (\Exception $e) {
            if (ob_get_level() !== 0) {
                ob_clean();
            }

            $handler = $this->serviceProvider->getExceptionHandler();
            $response = $handler->handle($e);
        }

        if ($sendResponse === true) {
            $this->sendResponse($response);
        }

        return $response;
    }

    /**
     * Dispatch the router
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function dispatchRouter(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $em = $this->serviceProvider->getEventManager();
        $event = new Event('', null, ['application' => $this]);
        $event->setName(self::EVENT_PRE_ROUTING);
        $em->trigger($event);

        $request = $this->serviceProvider->getRequest();
        $router = $this->serviceProvider->getRouter();
        $result = $router->match($request);
        $request = $request->withAttribute('router_result', $result);
        $params = $result->getParams();
        foreach ($params as $name => $value) {
            $request = $request->withAttribute($name, urldecode($value));
        }
        $this->serviceProvider->setRequest($request);

        $event->setName(self::EVENT_POST_ROUTING);
        $em->trigger($event);
        $request = $this->serviceProvider->getRequest();

        if ($result->isNotFound()) {
            if ($result->isMethodNotAllowed()) {
                $handler = $this->serviceProvider->getNotFoundHandler();
                $response = $handler->handle($result->getAllowedMethods());
            } else {
                $handler = $this->serviceProvider->getNotFoundHandler();
                $response = $handler->handle();
            }

            $this->serviceProvider->setResponse($response);

            return $next($request, $response);
        }

        foreach ($result->getRouteMiddlewares() as $k => $middleware) {
            if (is_string($middleware) && $this->serviceProvider->has($middleware)) {
                $middleware = $this->getMiddlewareFromService($middleware);
            }

            if (!is_callable($middleware)) {
                throw new \InvalidArgumentException('Middleware is not callable');
            }

            $this->middlewares[] = $middleware;
        }

        $this->middlewares[] = [$this, 'dispatch'];

        return $next($request, $response);
    }

    /**
     * Dispatch
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     * @throws Exception\InvalidRouterResultException
     * @throws Exception\InvalidRouterException
     * @throws Exception\ControllerNotFoundException
     * @throws Exception\InvalidControllerException
     */
    protected function dispatch(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $em = $this->serviceProvider->getEventManager();
        $event = new Event('', null, ['application' => $this]);
        $event->setName(self::EVENT_PRE_DISPATCH);
        $em->trigger($event);
        $request = $this->serviceProvider->getRequest();

        $result = $request->getAttribute('router_result');
        if ($result instanceof RouterResult === false) {
            throw new Exception\InvalidRouterResultException("Router result not isset in Request");
        }

        $handler = $result->getRouteHandler();
        $handler['controller'] = isset($handler['controller']) ? $handler['controller'] : 'index';
        $handler['action'] = isset($handler['action']) ? $handler['action'] : 'index';
        if (!is_string($handler['controller'])) {
            throw new Exception\InvalidRouterException('Invalid controller name in route handler');
        }
        if (!is_string($handler['action'])) {
            throw new Exception\InvalidRouterException('Invalid action name in route handler');
        }

        $controllerName = $handler['controller'];
        $controllers = $this->getConfig('controllers');
        $controllerClass = isset($controllers[$controllerName]) ? $controllers[$controllerName] : null;
        if (!class_exists($controllerClass)) {
            throw new Exception\ControllerNotFoundException(sprintf(
                'Controller "%s" not exist.',
                $controllerName
            ));
        }

        $r = new \ReflectionClass($controllerClass);
        if (!$r->isSubclassOf('Tlumx\Application\Controller')) {
            throw new Exception\InvalidControllerException(
                sprintf(
                    'Controller "%s" is not an instance of Tlumx\Application\Controller.',
                    $controllerClass
                )
            );
        }

        $request = $request->withAttribute('router_result_handler', $handler);
        $this->serviceProvider->setRequest($request);
        $controller = new $controllerClass($this->serviceProvider);
        $controller->run();

        $request = $this->serviceProvider->getRequest();
        $response = $this->serviceProvider->getResponse();

        $event->setName(self::EVENT_POST_DISPATCH);
        $em->trigger($event);
        $request = $this->serviceProvider->getRequest();

        return $next($request, $response);
    }

    /**
     * Send Response
     *
     * @param ResponseInterface $response
     */
    public function sendResponse(ResponseInterface $response)
    {
        if (headers_sent()) {
            return;
        }

        if (! $response->hasHeader('Content-Length') && (null !== $response->getBody()->getSize())) {
            $response = $response->withHeader('Content-Length', (string) $response->getBody()->getSize());
        }

        // Status Line
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        // Headers
        foreach ($response->getHeaders() as $header => $values) {
            $first = false;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $header, $value), $first);
                $first = false;
            }
        }

        // Body
        $body = $response->getBody();
        $body->rewind();

        while (!$body->eof()) {
            echo $body->read(4096);
            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }
        }
    }
}
