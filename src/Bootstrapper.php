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
use Tlumx\Application\ConfigureContainerInterface;
use Tlumx\Application\ApplicationEvent as AppEvent;

/**
 * Bootstrapper class.
 */
class Bootstrapper
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    private $container;

    /**
    * @var \Tlumx\Application\ConfigureContainerInterface
    */
    private $configure;

    /**
     * Constructor
     *
     * @param \Psr\Container\ContainerInterface $container
     * @var \Tlumx\Application\ConfigureContainerInterface $configure
     */
    public function __construct(ContainerInterface $container, ConfigureContainerInterface $configure)
    {
        $this->container = $container;
        $this->configure = $configure;

        $this->run();
    }

    /**
     * Get container
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
    * Get ConfigureContainer object
    *
    * @return ConfigureContainerInterface
    */
    public function getConfigureContainerObj()
    {
        return $this->configure;
    }

    /**
     * do Bootstrapper
     */
    private function run()
    {
        if (method_exists($this, 'init')) {
            $this->init();
        }

        if (method_exists($this, 'getConfig')) {
            $config = $this->getConfig();
            if (!is_array($config)) {
                throw new Exception\InvalidBootstrapperClassException(
                    sprintf(
                        "Method \"getConfig\" must return array of configuration, from Bootstrapper class: \"%s\".",
                        get_class($this)
                    )
                );
            }
            $this->getContainer()->get('config')->mergeTo($config);
        }

        if (method_exists($this, 'getServiceConfig')) {
            $configContainer = $this->getServiceConfig();
            if (!is_array($configContainer)) {
                throw new Exception\InvalidBootstrapperClassException(
                    sprintf(
                        "Method \"getServiceConfig\" must return array of services configuration," .
                            "from Bootstrapper class: \"%s\".",
                        get_class($this)
                    )
                );
            }
            $this->getConfigureContainerObj()->configureContainer($this->getContainer(), $configContainer);
        }

        if (method_exists($this, 'postBootstrap')) {
            $this->getContainer()->get('event_manager')->attach(AppEvent::EVENT_POST_BOOTSTRAP, function ($e) {
                return $this->postBootstrap();
            });
        }

        if (method_exists($this, 'preRouting')) {
            $this->getContainer()->get('event_manager')->attach(AppEvent::EVENT_PRE_ROUTING, function ($e) {
                return $this->preRouting();
            });
        }

        if (method_exists($this, 'postRouting')) {
            $this->getContainer()->get('event_manager')->attach(AppEvent::EVENT_POST_ROUTING, function ($e) {
                return $this->postRouting();
            });
        }

        if (method_exists($this, 'preDispatch')) {
            $this->getContainer()->get('event_manager')->attach(AppEvent::EVENT_PRE_DISPATCH, function ($e) {
                return $this->preDispatch();
            });
        }

        if (method_exists($this, 'postDispatch')) {
            $this->getContainer()->get('event_manager')->attach(AppEvent::EVENT_POST_DISPATCH, function ($e) {
                return $this->postDispatch();
            });
        }
    }
}
