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

/**
 * Interface for configuration Psr\Container\ContainerInterface.
 */
interface ConfigureContainerInterface
{
    /**
     * Configure given Psr\Container\ContainerInterface
     *
     * @param ContainerInterface $container
     * @param array $config
     * @return void
     */
    public function configureContainer(ContainerInterface $container, array $config = []) : void;
}
