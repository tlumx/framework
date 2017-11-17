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

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Tlumx\ServiceContainer\Exception\ContainerException;
use Tlumx\ServiceContainer\Exception\NotFoundException;

/**
 * Simple dependency injection (DI) container.
 * ServiceContainer is a PSR-11 container implementation,
 * and it implement the Psr\Container\ContainerInterface.
 */
class ServiceContainer implements PsrContainerInterface
{
    /**
     * @var array service name => boolean flag, indicating whether or not
     *     the service is shared.
     */
    protected $keys = [];

    /**
     * @var array A list of services or parameters.
     */
    protected $values = [];

    /**
     * @var array service names that are cannot be overridden.
     */
    protected $immutable = [];

    /**
     * @var array alias => service name pairs.
     */
    protected $aliases = [];

    /**
     * @var array service name => factory pairs.
     */
    protected $factories = [];

    /**
     * @var array service name => array of service definition pairs.
     */
    protected $definition = [];

    /**
     * Constructor - instantiate the container.
     *
     * @param array $values Objects and parameters
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $id => $value) {
            $this->values[$id] = $value;
            $this->keys[$id] = true;
        }
    }

    /**
     * Get entry of the container by its identifier.
     *
     * @param string $id Identifier of the service.
     * @return mixed Entry of the container.
     * @throws NotFoundException If service not exist in container.
     */
    public function get($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (!isset($this->keys[$id])) {
            throw new NotFoundException(sprintf(
                'The service "%s" is not found',
                $id
            ));
        }

        if (isset($this->values[$id])) {
            return $this->values[$id];
        }

        if (! $this->keys[$id]) {
            if (isset($this->factories[$id])) {
                return $this->createFromFactory($this->factories[$id]);
            } else {
                return $this->createFromDefinition($this->definition[$id]);
            }
        }

        if (isset($this->factories[$id])) {
            $service = $this->createFromFactory($this->factories[$id]);
            unset($this->factories[$id]);
        } else {
            $service = $this->createFromDefinition($this->definition[$id]);
            unset($this->definition[$id]);
        }

        $this->values[$id] = $service;
        $this->immutable[$id] = true;
        return $service;
    }

    /**
     * Set entry to container: parameter or an object.
     *
     * @param string $id Identifier for the parameter or object.
     * @param mixed $value The value of the parameter or define object.
     * @throws ContainerException Error while service with this identifier
     *     already exists and cannot be overridden.
     */
    public function set($id, $value)
    {
        if (isset($this->immutable[$id])) {
            throw new ContainerException(sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $id
            ));
        }

        $this->values[$id] = $value;
        $this->keys[$id] = true;

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }
    }

    /**
     * Set entry alias in the container
     *
     * @param string $alias Identifier for entry alias.
     * @param string $service Identifier of the service for which the alias is define.
     * @throws ContainerException Error while service name and aliad are equals.
     */
    public function setAlias($alias, $service)
    {
        if ($alias == $service) {
            throw new ContainerException('Alias and service names can not be equals');
        }

        $this->aliases[$alias] = $service;
    }

    /**
     * Remove alias name from container.
     *
     * @param string $alias Identifier for entry alias.
     */
    public function removeAlias($alias)
    {
        if (isset($this->aliases[$alias])) {
            unset($this->aliases[$alias]);
        }
    }

    /**
     * Returns true if alias name is isset, and false - otherwise.
     *
     * @param string $alias Identifier for entry alias.
     * @return bool True - if alias name is isset, and false - otherwise.
     */
    public function hasAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Retrive all registred aliases.
     *
     * @return array All registred aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Retrive service identifier for alias or default value, if alias is not
     * define.
     *
     * @param string $alias Alias identifier.
     * @param mixed $default Default value, if alias not registred.
     * @return string|mixed Service identifier or default value.
     */
    public function getServiceIdFromAlias($alias, $default = null)
    {
        if (isset($this->aliases[$alias])) {
            return $this->aliases[$alias];
        }

        return $default;
    }

    /**
     * Returns true if the container can return an entry for the given
     * identifier and returns false otherwise.
     *
     * @param string $id Identifier of the service.
     * @return bool True - if the container can return an entry for the given
     *     identifier, false - otherwise.
     */
    public function has($id)
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return isset($this->keys[$id]);
    }

    /**
     * Remove an entry of the container by its identifier.
     *
     * @param string $id Identifier of the service.
     */
    public function remove($id)
    {
        if (isset($this->aliases[$id])) {
            $aliasId = $id;
            $id = $this->aliases[$id];
            unset($this->aliases[$aliasId]);
        }

        unset($this->keys[$id]);
        unset($this->values[$id]);
        unset($this->immutable[$id]);
        unset($this->factories[$id]);
        unset($this->definition[$id]);
    }

    /**
     * Register service.
     *
     * Service may be callable or string (that can be resolving to an invokable
     * class or to a FactoryInterface instance).
     *
     * When called, one argument is passed to the function - this Container.
     *
     * @param string $id Identifier of the service.
     * @param string|callable $service Any callable service or class name.
     * @param bool $isShared Indicating whether or not the service is shared.
     * @throws ContainerException Error while service already exists and cannot
     *     be overridden.
     */
    public function register($id, $service, $isShared = true)
    {
        if (isset($this->immutable[$id])) {
            throw new ContainerException(sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $id
            ));
        }

        $this->factories[$id] = $service;
        $this->keys[$id] = boolval($isShared);

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }
    }

    /**
     * Register array of definition, which will be used to create the service.
     *
     * @param string $id Identifier of the service.
     * @param array $definition Array of service definition.
     * @param bool $isShared Indicating whether or not the service is shared.
     * @throws ContainerException Error while service already exists and cannot
     *     be overridden.
     */
    public function registerDefinition($id, array $definition, $isShared = true)
    {
        if (isset($this->immutable[$id])) {
            throw new ContainerException(sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $id
            ));
        }

        $this->definition[$id] = $definition;
        $this->keys[$id] = boolval($isShared);

        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }
    }

    /**
     * Create a new instance of service from factory.
     *
     * @param string|callable $factory The service factory.
     * @return mixed New service.
     * @throws ContainerException Error while service could not be created.
     */
    protected function createFromFactory($factory)
    {
        try {
            if (is_string($factory) && class_exists($factory)) {
                $factory = new $factory();
            }

            if (is_callable($factory)) {
                $service  = $factory($this);
            } else {
                throw new ContainerException(
                    'Service could not be created: "There were incorrectly '
                        . 'transmitted data when registering the service".'
                );
            }
        } catch (ContainerException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ContainerException(
                'Service could not be created: "Service factory may be '
                    . 'callable or string (that can be resolving to an '
                    . 'invokable class or to a FactoryInterface instance).'
            );
        }

        return $service;
    }

    /**
     * Create a new instance of service from array definition.
     *
     * @param array $definition Servise definition.
     * @return mixed New service.
     * @throws ContainerException Error while service could not be created.
     */
    protected function createFromDefinition(array $definition)
    {
        if (!isset($definition['class'])) {
            throw new ContainerException(
                'Service could not be created from definition:'
                    . ' option "class" is not exists in definition array.'
            );
        }

        $className = $definition['class'];

        if (!class_exists($className)) {
            throw new ContainerException(sprintf(
                'Service could not be created from definition:'
                    . ' Class "%s" is not exists.',
                $className
            ));
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf(
                'Service could not be created from definition:'
                    . ' Unable to create instance of class "%s".',
                $className
            ));
        }

        if (null !== ($constructor = $reflection->getConstructor())) {
            if (!isset($definition['args'])) {
                throw new ContainerException(
                    'Service could not be created from definition:'
                        . ' option "args" is not exists in definition array.'
                );
            }
            $params = $this->resolveArgs($constructor->getParameters(), $definition['args']);
            $service = $reflection->newInstanceArgs($params);
        } else {
            // we don't have a constructor
            $service = $reflection->newInstance();
        }

        if (!isset($definition['calls'])) {
            return $service;
        }

        $calls = isset($definition['calls']) ? $definition['calls'] : [];
        foreach ($calls as $method => $args) {
            if (!is_callable([$service, $method])) {
                throw new ContainerException(sprintf(
                    'Service could not be created from definition:'
                        . ' can not call method "%s" from class: "%s"',
                    $method,
                    $className
                ));
            }
            $method = new \ReflectionMethod($service, $method);
            $params = $method->getParameters();
            $arguments = is_array($args)
                    ? $this->resolveArgs($params, $args)
                    : [];
            $method->invokeArgs($service, $arguments);
        }

        return $service;
    }

    /**
     * Resolve arguments form function/methods to servise array definition.
     *
     * @param array $params Array of ReflectionParameter.
     * @param array $definitionArgs Array parameters from definition array.
     * @return array Resolved array.
     * @throws ContainerException Unable resolve parameter.
     */
    protected function resolveArgs(array $params, array $definitionArgs)
    {
        $args = [];
        foreach ($params as $key => $param) {
            $paramName = $param->name;

            if (isset($definitionArgs[$paramName])) {
                $value = $definitionArgs[$paramName];
            } elseif (isset($definitionArgs[$key])) {
                $value = $definitionArgs[$key];
            } elseif ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
                continue;
            } else {
                throw new ContainerException(
                    'Service could not be created from definition:'
                        . ' unable resolve parameter.'
                );
            }

            if (!is_array($value)) {
                $args[] = $value;
                continue;
            }

            if ((count($value) == 1) && isset($value['ref'])) {
                if ($this->has($value['ref'])) {
                    $args[] = $this->get($value['ref']);
                    continue;
                } elseif ($value['ref'] == 'this') {
                    $args[] = $this;
                    continue;
                }

                throw new ContainerException(
                    'Service could not be created from definition:'
                        . ' unable resolve parameter.'
                );
            }

            $args[] = $value;
        }

        return $args;
    }
}
