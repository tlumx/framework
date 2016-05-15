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

/**
 * Bootstrapper class.
 */
class Bootstrapper
{
    /**
     * Application object
     *
     * @var \Tlumx\Application
     */
    private $_app;

    /**
     * Constructor
     *
     * @param \Tlumx\Application $app
     */
    public function __construct(Application $app)
    {
        $this->_app = $app;
        
        $this->_run();
    }

    /**
     * Get ServiceProvider object
     *
     * @return \Tlumx\ServiceProvider
     */
    public function getServiceProvider()
    {
        return $this->_app->getServiceProvider();
    }

    /**
     * Get application config
     *
     * @param string $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        return $this->getServiceProvider()->getConfig($option);
    }

    /**
     * Set application config
     *
     * @param string $option
     * @param mixed $value
     */
    public function setConfig($option, $value = null)
    {
        $this->getServiceProvider()->setConfig($option, $value);
    }

    /**
     * Add application middleware
     *
     * @param mixed $middleware
     */
    public function addMiddleware($middleware)
    {
        $this->_app->add($middleware);
    }

    /**
     * do Bootstrapper
     */
    private function _run()
    {
        if(method_exists($this, 'init')) {
            $this->init();
        }
        
        if(method_exists($this, 'postBootstrap')) {
            $this->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_BOOTSTRAP, function (){
                $this->postBootstrap();
            });
        }
        
        if(method_exists($this, 'preRouting')) {
            $this->getServiceProvider()->getEventManager()->addListener(Application::EVENT_PRE_ROUTING, function (){
                $this->preRouting();
            });
        }
        
        if(method_exists($this, 'postRouting')) {
            $this->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_ROUTING, function () {
                $this->postRouting();
            });
        }
        
        if(method_exists($this, 'preDispatch')) {
            $this->getServiceProvider()->getEventManager()->addListener(Application::EVENT_PRE_DISPATCH, function () {
                $this->preDispatch();
            });
        }
        
        if(method_exists($this, 'postDispatch')) {
            $this->getServiceProvider()->getEventManager()->addListener(Application::EVENT_POST_DISPATCH, function () {
                $this->postDispatch();
            });
        }
    }
}