<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\ServiceContainer;

use Psr\Container\ContainerInterface;
use Tlumx\ServiceContainer\Exception\ServiceNotCreatedException;
use Tlumx\ServiceContainer\Exception\ServiceNotFoundException;

interface FactoryInterface
{
    /**
    * Create an service object.
    *
    * @param ContainerInterface $container
    * @return $object Service.
    * @throws ServiceNotFoundException if unable to resolve the service.
    * @throws ServiceNotCreatedException if an exception is raised when creating a service.
    * @throws ContainerException if any other error occurs.
    */
    public function __invoke(ContainerInterface $container);
}
