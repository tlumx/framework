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

use Tlumx\EventManager\Event;
use Psr\Container\ContainerInterface;

/*
 * Application Event.
 */
class ApplicationEvent extends Event
{
    /**
     * Application events triggered by eventmanager
     */
    const EVENT_POST_BOOTSTRAP = 'event.post.bootstrap';
    const EVENT_PRE_ROUTING = 'event.pre.routing';
    const EVENT_POST_ROUTING = 'event.post.routing';
    const EVENT_PRE_DISPATCH = 'event.pre.dispatch';
    const EVENT_POST_DISPATCH = 'event.post.dispatch';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Set container instance
     *
     * @param  ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $params = $this->getParams();
        $params['container'] = $container;
        $this->setParams($params);
        $this->container = $container;
    }

    /**
     * Get container instance
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
}
