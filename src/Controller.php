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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Tlumx\ServiceContainer\FactoryInterface;
use Tlumx\Router\Result as RouteResult;

/**
 * Controller class and it's also PSR-15 Psr\Http\Server\RequestHandlerInterface wrapper.
 */
class Controller implements RequestHandlerInterface, FactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $controllerNamecontainer = 'index';

    /**
     * @var string
     */
    private $action = 'index';

    /**
     * @var \Tlumx\View\View
     */
    private $view;

    /**
     * @var string
     */
    private $layout;

    /**
     * @var bool
     */
    private $enableLayout = false;

    /**
     * Get application container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get View
     *
     * @return \Tlumx\View\View
     */
    public function getView()
    {
        if (!$this->view) {
            $this->view = $this->getContainer()->get('view');
            if ($this->getContainer()->get('templates_manager')->hasTemplatePath($this->controllerNamecontainer)) {
                $tm = $this->getContainer()->get('templates_manager');
                $path = $tm->getTemplatePath($this->controllerNamecontainer);
                $this->view->setTemplatesPath($path);
            }
        }

        return $this->view;
    }

    /**
     * Render
     *
     * @return string
     */
    public function render()
    {
        return $this->getView()->render($this->action);
    }

    /**
     * Set layout
     *
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function setLayout($name)
    {
        if (!is_string($name) || empty($name)) {
            throw new \InvalidArgumentException('Layout name must be not empty string.');
        }
        $this->layout = $name;
        $this->enableLayout(true);
    }

    /**
     * Get layout
     *
     * @return string|null
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Enable layout (set and get Enabled status)
     *
     * @param bool|null $flag
     * @return bool
     */
    public function enableLayout($flag = null)
    {
        if ($flag === true) {
            $this->enableLayout = true;
        } elseif ($flag === false) {
            $this->enableLayout = false;
        }
        return $this->enableLayout;
    }

    /**
     * Create an service object (from container).
     *
     * @param ContainerInterface $container
     * @return $object Service (this)
     */
    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Handle the request and return a response.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception\RouterResultNotFoundException
     * @throws Exception\ActionNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);
        if (! $routeResult || ! $routeResult->isSuccess()) {
            throw new Exception\RouterResultNotFoundException(
                "Must be a valid \"Tlumx\Router\Result\" objrct in the request attriute"
            );
        }

        $handler = $routeResult->getRouteHandler();
        $this->controllerNamecontainer = isset($handler['controller']) ? $handler['controller'] : 'index';
        $this->action = isset($handler['action']) ? $handler['action'] : 'index';
        $action = $this->action . 'Action';
        if (!method_exists($this, $action)) {
            throw new Exception\ActionNotFoundException(sprintf('Action "%s" not found.', $this->action));
        }

        $layout = $this->getContainer()->get('config')->get('layout');
        if (is_string($layout) && $layout) {
            $this->setLayout($layout);
        }

        $actionResponse = $this->$action($request);
        if ($actionResponse instanceof ResponseInterface) {
            return $actionResponse;
        }

        if ($this->enableLayout()) {
            $layoutFile = $this->getContainer()->get('templates_manager')->getTemplate($this->getLayout());
            $this->getView()->content = $actionResponse;
            $actionResponse = $this->getView()->renderFile($layoutFile);
        }

        $response = $this->getContainer()->get('response');
        $response->getBody()->write($actionResponse);
        return $response;
    }

    /**
     * Create route path
     *
     * @param string $routeName
     * @param array $data
     * @param array $queryParams
     * @return string
     */
    public function uriFor($routeName, array $data = [], array $queryParams = [])
    {
        return $this->getContainer()->get('router')->uriFor($routeName, $data, $queryParams);
    }

    /**
     * Create response: redirect to url
     *
     * @param string $url
     * @param int $status
     * @return ResponseInterface
     */
    public function redirect($url, $status = 302)
    {
        $response = $this->getContainer()->get('response')->withHeader('Location', $url);
        $response = $response->withStatus($status);
        return $response;
    }

    /**
     * Create response: redirect to route
     *
     * @param string $routeName
     * @param array $data
     * @param array $queryParams
     * @param int $status
     * @return ResponseInterface
     */
    public function redirectToRoute($routeName, array $data = [], array $queryParams = [], $status = 302)
    {
        $url = $this->uriFor($routeName, $data, $queryParams);
        return $this->redirect($url, $status);
    }
}
