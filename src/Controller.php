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

use Tlumx\Application\ServiceProvider;

/**
 * Controller class.
 */
class Controller
{
    /**
     * @var \Tlumx\ServiceProvider
     */
    private $_provider;

    /**
     * @var string
     */
    private $_controllerName = 'index';

    /**
     * @var string
     */
    private $_action = 'index';

    /**
     * @var \Tlumx\View\View
     */
    private $_view;

    /**
     * @var string
     */
    private $_layout;

    /**
     * @var bool
     */
    private $_enableLayout = false;

    /**
     * Construct
     *
     * @param \Tlumx\ServiceProvider $provider
     */
    public function __construct(ServiceProvider $provider)
    {
        $this->_provider = $provider;
    }

    /**
     * Get application Service Container
     *
     * @return \Tlumx\ServiceProvider
     */
    public function getServiceProvider()
    {
        return $this->_provider;
    }

    /**
     * Get View
     *
     * @return \Tlumx\View\View
     */
    public function getView()
    {
        if(!$this->_view) {
            $this->_view = $this->getServiceProvider()->getView();
            if($this->getServiceProvider()->getTemplatesManager()->hasTemplatePath($this->_controllerName)) {
                $path = $this->getServiceProvider()->getTemplatesManager()->getTemplatePath($this->_controllerName);
                $this->_view->setTemplatesPath($path);
            }
        }
        
        return $this->_view;
    }

    /**
     * Render
     *
     * @return string
     */
    public function render()
    {
        return $this->getView()->render($this->_action);
    }

    /**
     * Set layout
     *
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function setLayout($name)
    {
        if(!is_string($name) || !$name) {
            throw new \InvalidArgumentException('Layout name must be not empty string.');
        }
        $this->_layout = $name;
        $this->enableLayout(true);
    }

    /**
     * Get layout
     *
     * @return string|null
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Enable layout
     *
     * @param bool|null $flag
     * @return bool
     */
    public function enableLayout($flag = null)
    {
        if($flag === true) {
            $this->_enableLayout = true;
        } elseif($flag === false) {
            $this->_enableLayout = false;
        }
        return $this->_enableLayout;
    }

    /**
     * Run
     *
     * @throws Exception\ActionNotFoundException
     */
    public function run()
    {
        $request = $this->getServiceProvider()->getRequest();
        $handler = $request->getAttribute('router_result_handler');
        $this->_controllerName = $handler['controller'];
        $this->_action = $handler['action'];        
        $action = $this->_action . 'Action';
        if (!method_exists($this, $action)) {
            throw new Exception\ActionNotFoundException(sprintf('Action "%s" not found.', $this->_action));
        }
        
        $layout = $this->getServiceProvider()->getConfig('layout');
        if(is_string($layout) && $layout) {
            $this->setLayout($layout);
        }
        
        ob_start();
        $this->$action();
        $content = ob_get_clean();
        
        if($this->enableLayout()) {
            $layoutFile = $this->getServiceProvider()->getTemplatesManager()->getTemplate($this->getLayout());
            $this->getView()->content = $content;
            $content = $this->getView()->renderFile($layoutFile);
        }
        
        $response = $this->getServiceProvider()->getResponse();
        $response->getBody()->write($content);
        $this->getServiceProvider()->setResponse($response);
    }

    /**
     * Create route path
     *
     * @param string $routeName
     * @param array $args
     * @param array $query
     * @return string
     */
    public function uriFor($routeName, array $args = [], array $query = [])
    {
        return $this->getServiceProvider()->getRouter()->uriFor($routeName, $args, $query);
    }

    /**
     * Redirect to url
     *
     * @param string $url
     * @param int $status
     */
    public function redirect($url, $status = 302)
    {
        $response = $this->getServiceProvider()->getResponse()->withHeader('Location', $url);
        $response = $response->withStatus($status);
        $this->getServiceProvider()->setResponse($response);
    }

    /**
     * Redirect to route
     *
     * @param string $routeName
     * @param array $args
     * @param array $query
     * @param int $status
     */
    public function redirectToRoute($routeName, array $args = [], array $query = [], $status = 302)
    {
        $url = $this->uriFor($routeName, $args, $query);
        $this->redirect($url, $status);
    }
}

