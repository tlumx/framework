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
use Tlumx\ServiceContainer\ServiceContainer;

/**
 * Class for configuration Tlumx\ServiceContaine\ServiceContainer.
 */
class ConfigureTlumxContainer implements ConfigureContainerInterface
{
    /**
     * Configure given Psr\Container\ContainerInterface
     *
     * @param ContainerInterface $container
     * @param array $config
     * @return void
     */
    public function configureContainer(ContainerInterface $container, array $config = []) : void
    {
        if (!($container instanceof ServiceContainer)) {
            return;
        }

        $services = [];
        if (isset($config['service_container']) && is_array($config['service_container'])) {
            $services = $config['service_container'];
        }

        if (isset($services['services']) && is_array($services['services'])) {
            foreach ($services['services'] as $name => $service) {
                $container->set($name, $service);
            }
        }

        if (isset($services['factories']) && is_array($services['factories'])) {
            foreach ($services['factories'] as $name => $factory) {
                $container->register($name, $factory, $this->isShared($services, $name));
            }
        }

        if (isset($services['definitions']) && is_array($services['definitions'])) {
            foreach ($services['definitions'] as $name => $definition) {
                $container->registerDefinition($name, $definition, $this->isShared($services, $name));
            }
        }

        if (isset($services['aliases']) && is_array($services['aliases'])) {
            foreach ($services['aliases'] as $alias => $service) {
                $container->setAlias($alias, $service);
            }
        }
    }

    /**
     * Check if service is shared.
     * (it is true by default)
     *
     * @param array serviceContainerConfig
     * @param string serviceName
     * @return bool
     */
    private function isShared(array $serviceContainerConfig, string $serviceName)
    {
        $sharedConfig = isset($serviceContainerConfig['shared']) ? $serviceContainerConfig['shared'] : [];

        if (isset($sharedConfig[$serviceName]) && !$sharedConfig[$serviceName]) {
            return false;
        }

        return true;
    }
}
