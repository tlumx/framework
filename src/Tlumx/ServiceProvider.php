<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx;

use Tlumx\ServiceContainer\ServiceContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tlumx\Http\ServerRequest;
use Tlumx\Http\Response;
use Tlumx\Router\RouterInterface;
use Tlumx\Router\Router;
use Tlumx\EventManager\EventManager;
use Tlumx\View\ViewInterface;
use Tlumx\View\View;
use Tlumx\View\TemplatesManager;
use Tlumx\Handler\ExceptionHandlerInterface;
use Tlumx\Handler\DefaultExceptionHandler;
use Tlumx\Handler\NotFoundHandlerInterface;
use Tlumx\Handler\DefaultNotFoundHandler;

/**
 * Service provider class.
 */
class ServiceProvider extends ServiceContainer
{
    /**
     * Default config
     *
     * @var array
     */
    protected $config = [
        'error_reporting' => '0',
        'display_errors' => '0',
        'display_exceptions' => false,
    ];

    /**
     * Request
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * Response
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Router
     *
     * @var \Tlumx\Router\RouterInterface
     */
    protected $router;

    /**
     * EventManager
     *
     * @var Tlumx\EventManager\EventManager
     */
    protected $eventManager;

    /**
     * View
     *
     * @var \Tlumx\View\ViewInterface
     */
    protected $view;

    /**
     * TemplatesManager
     *
     * @var \Tlumx\View\TemplatesManager
     */
    protected $templatesManager;

    /**
     * ExceptionHandler
     *
     * @var \Tlumx\Handler\ExceptionHandlerInterface
     */
    protected $exceptionHandler;

    /**
     * NotFoundHandler
     *
     * @var \Tlumx\Handler\NotFoundHandlerInterface
     */
    protected $notFoundHandler;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct([]);
        $this->setConfig($config);
    }

    /**
     * Get config
     * 
     * @param string|null $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        if($option === null) {
            return $this->config;
        }
        
        return isset($this->config[$option]) ? $this->config[$option] : null;
    }

    /**
     * Set config
     * 
     * @param string $option
     * @param mixed $value
     */
    public function setConfig($option, $value = null)
    {
        if(is_array($option)) {
            $this->config = array_replace_recursive($this->config, $option);
            return;
        }
        
        $this->config[$option] = $value;
    }

    /**
     * Get request
     * 
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \RuntimeException
     */
    public function getRequest()
    {
        if($this->request) {
            return $this->request;
        }
        
        if($this->has('request')) {
            $this->request = $this->get('request');
            if (!$this->request instanceof ServerRequestInterface) {
                throw new \RuntimeException(
                    'The Request service must return an instance of \Psr\Http\Message\ServerRequestInterface.'
                );
            }
        } else {
            $this->request = ServerRequest::createFromGlobal();
        }
        
        return $this->request;
    }

    /**
     * Set request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException
     */
    public function getResponse()
    {
        if($this->response) {
            return $this->response;
        }
        
        if($this->has('response')) {
            $this->response = $this->get('response');
            if (!$this->response instanceof ResponseInterface) {
                throw new \RuntimeException(
                    'The Response service must return an instance of \Psr\Http\Message\ResponseInterface.'
                );
            }
        } else {
            $this->response = new Response();
        }
        
        return $this->response;
    }

    /**
     * Set response
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Get router
     *
     * @return \Tlumx\Router\RouterInterface
     * @throws \RuntimeException
     */
    public function getRouter()
    {
        if($this->router) {
            return $this->router;
        }
        
        if($this->has('router')) {
            $this->router = $this->get('router');
            if (!$this->router instanceof RouterInterface) {
                throw new \RuntimeException(
                    'The Router service must return an instance of \Tlumx\Router\RouterInterface.'
                );
            }
        } else {
            $this->router = new Router();
        }
        
        $basePath = $this->getConfig('base_path');
        if(is_string($basePath) && $basePath) {
            $this->router->setBasePath($basePath);
        }
        
        $routes = $this->getConfig('routes');
        if($routes) {
            $this->router->setRoutes($routes);
        }
        
        return $this->router;
    }

    /**
     * Set router
     *
     * @param \Tlumx\Router\RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Get EventManagr
     *
     * @return \Tlumx\EventManager\EventManager
     * @throws \RuntimeException
     */
    public function getEventManager()
    {
        if(!$this->eventManager) {
            $this->eventManager = new EventManager();
            $listeners = $this->getConfig('listeners');
            if(is_array($listeners)) {
                foreach($listeners as $listener) {
                    if(!class_exists($listener)) {
                        throw new \RuntimeException(sprintf(
                                'Event listener class "%s" does not exist',
                                $listener
                        ));
                    }
                    $instance = new $listener;
                    $this->eventManager->addListenerObject($instance);
                }
            }
        }
        
        return $this->eventManager;
    }

    /**
     * Set EventManager
     *
     * @param \Tlumx\EventManager\EventManager $eventManager
     */
    public function setEventManager(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Get view
     *
     * @return \Tlumx\View\ViewInterface
     * @throws \RuntimeException
     */
    public function getView()
    {
        if($this->view) {
            return $this->view;
        }
        
        if($this->has('view')) {
            $this->view = $this->get('view');
            if (!$this->view instanceof ViewInterface) {
                throw new \RuntimeException(
                    'The View service must return an instance of \Tlumx\View\ViewInterface.'
                );
            }
        } else {
            $this->view = new View();
        }
        
        return $this->view;
    }

    /**
     * Set view
     *
     * @param \Tlumx\View\ViewInterface $view
     */
    public function setView(ViewInterface $view)
    {
        $this->view = $view;
    }

    /**
     * TemplatesManager
     *
     * @return \Tlumx\View\TemplatesManager
     */
    public function getTemplatesManager()
    {
        if(!$this->templatesManager) {
            $this->templatesManager = new TemplatesManager();
            $templatesPaths = $this->getConfig('templates_paths', []);
            if($templatesPaths) {
                $this->templatesManager->setTemplatePaths($templatesPaths);
            }
            $templates = $this->getConfig('templates', []);
            if($templates) {
                $this->templatesManager->setTemplateMap($templates);
            }
        }
        
        return $this->templatesManager;
    }

    /**
     * Set TemplatesManager
     *
     * @param \Tlumx\View\TemplatesManager $templatesManager
     */
    public function setTemplatesManager(TemplatesManager $templatesManager)
    {
        $this->templatesManager = $templatesManager;
    }

    /**
     * ExceptionHandler
     *
     * @return \Tlumx\Handler\ExceptionHandlerInterface
     * @throws \RuntimeException
     */
    public function getExceptionHandler()
    {
        if($this->exceptionHandler) {
            return $this->exceptionHandler;
        }
        
        if($this->has('exception_handler')) {
            $this->exceptionHandler = $this->get('exception_handler');
            if (!$this->exceptionHandler instanceof ExceptionHandlerInterface) {
                throw new \RuntimeException(
                    'The ExceptionHandler service must return an instance of \Tlumx\Handler\ExceptionHandlerInterface.'
                );
            }
        } else {
            $this->exceptionHandler = new DefaultExceptionHandler($this);
        }
        
        return $this->exceptionHandler;        
    }

    /**
     * ExceptionHandler
     *
     * @param \Tlumx\Handler\ExceptionHandlerInterface $handler
     */
    public function setExceptionHandler(ExceptionHandlerInterface $handler)
    {
        $this->exceptionHandler = $handler;
    }

    /**
     * NotFoundHandler
     *
     * @return \Tlumx\Handler\NotFoundHandlerInterface
     * @throws \RuntimeException
     */
    public function getNotFoundHandler()
    {
        if($this->notFoundHandler) {
            return $this->notFoundHandler;
        }
        
        if($this->has('not_found_handler')) {
            $this->notFoundHandler = $this->get('not_found_handler');
            if (!$this->notFoundHandler instanceof NotFoundHandlerInterface) {
                throw new \RuntimeException(
                    'The NotFoundHandler service must return an instance of \Tlumx\Handler\NotFoundHandlerInterface.'
                );
            }
        } else {
            $this->notFoundHandler = new DefaultNotFoundHandler($this);
        }
        
        return $this->notFoundHandler;
    }

    /**
     * NotFoundHandler
     *
     * @param \Tlumx\Handler\NotFoundHandlerInterface $handler
     */
    public function setNotFoundHandler(NotFoundHandlerInterface $handler)
    {
        $this->notFoundHandler = $handler;
    }
}