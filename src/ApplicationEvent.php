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
    * @var Application
    */
    protected $application;

    /**
     * Set application instance
     *
     * @param  Application $application
     */
    public function setApplication(Application $application)
    {
        $params = $this->getParams();
        $params['application'] = $application;
        $this->setParams($params);
        $this->application = $application;
    }

    /**
     * Get application instance
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }
}
