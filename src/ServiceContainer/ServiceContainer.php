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
 * ServiceProvider is a PSR-11 container implementation,
 * and it implement the Psr\Container\ContainerInterface.
 */
class ServiceContainer implements PsrContainerInterface
{
    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $shared = [];

    /**
     * @var array
     */
    protected $protected = [];

    /**
     * @var array
     */
    protected $immutable = [];

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $id => $value) {
            $this->values[$id] = $value;
            $this->keys[$id] = true;
        }
    }

    /**
     * Get entry of the container by its identifier
     *
     * @param mixed $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id)
    {
        if (!isset($this->keys[$id])) {
            throw new NotFoundException(sprintf(
                'The service "%s" is not found',
                $id
            ));
        } elseif (isset($this->values[$id])) {
            return $this->values[$id];
        } elseif (isset($this->protected[$id])) {
            return $this->protected[$id]($this);
        }

        $this->values[$id] = $val = $this->shared[$id]($this);
        $this->immutable[$id] = true;
        unset($this->shared[$id]);
        return $val;
    }

    /**
     * Get entry in the container
     *
     * @param mixed $id
     * @param mixed $value
     * @throws ContainerException
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
    }

    /**
     * Set entry alias in the container
     *
     * @param mixed $alias
     * @param mixed $service
     * @throws ContainerException
     */
    public function setAlias($alias, $service)
    {
        if ($alias == $service) {
            throw new ContainerException('Alias and service names can not be equals');
        }

        $this->protected[$alias] = function () use ($service) {
            return $this->get($service);
        };
        $this->keys[$alias] = true;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param mixed $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->keys[$id]);
    }

    /**
     * Remove an entry of the container by its identifier
     *
     * @param mixed $id
     */
    public function remove($id)
    {
        unset($this->keys[$id]);
        unset($this->values[$id]);
        unset($this->shared[$id]);
        unset($this->protected[$id]);
        unset($this->immutable[$id]);
    }

    /**
     * Register service
     *
     * @param mixed $id
     * @param mixed $service
     * @param bool $isShared
     * @return \Tlumx\ServiceContainer\ServiceContainer
     * @throws ContainerException
     */
    public function register($id, $service, $isShared = true)
    {
        if (isset($this->immutable[$id])) {
            throw new ContainerException(sprintf(
                'A service by the name "%s" already exists and cannot be overridden',
                $id
            ));
        }

        if ($service instanceof \Closure) {
            if ($isShared) {
                $this->shared[$id] = $service;
                $this->keys[$id] = true;
                return;
            } else {
                $this->protected[$id] = $service;
                $this->keys[$id] = true;
                return;
            }
        } elseif (is_array($service)) {
            if ($isShared) {
                $this->shared[$id] = function () use ($service) {
                    return $this->createFromDefinition($service);
                };
                $this->keys[$id] = true;
                return $this;
            } else {
                $this->protected[$id] = function () use ($service) {
                    return $this->createFromDefinition($service);
                };
                $this->keys[$id] = true;
                return;
            }
        }

        $this->values[$id] = $service;
        $this->keys[$id] = true;
    }

    /**
     * Create service from array definition
     *
     * @param array $definition
     * @return mixed
     * @throws ContainerException
     */
    protected function createFromDefinition(array $definition)
    {
        if (!isset($definition['class'])) {
            throw new ContainerException(
                'Option "class" is not exists in definition array'
            );
        }

        $className = $definition['class'];

        if (!class_exists($className)) {
            throw new ContainerException(
                'Class "' . $className . '" is not exists'
            );
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(
                'Unable to create instance of class: "' . $className.'"'
            );
        }

        /*$constructor = $reflection->getConstructor();
        $params = [];
        if ($constructor) {
            if (!isset($definition['args'])) {
                throw new ContainerException(
                    'Option "args" is not exists in definition array'
                );
            }
            $params = $this->resolveArgs($constructor->getParameters(), $definition['args']);
        }

        $service = $reflection->newInstanceArgs($params);
        */
        
        if (null !== ($constructor = $reflection->getConstructor())) {
            // we have a constructor
            $params = [];
	    if ($constructor) {
		if (!isset($definition['args'])) {
		    throw new ContainerException(
			'Option "args" is not exists in definition array'
		    );
		}
		$params = $this->resolveArgs($constructor->getParameters(), $definition['args']);
	    }

	    $service = $reflection->newInstanceArgs($params);
        } else {
	    $service = $reflection->newInstance();
        }

        if (!isset($definition['calls'])) {
            return $service;
        }

        $calls = isset($definition['calls']) ? $definition['calls'] : [];
        foreach ($calls as $method => $args) {
            if (!is_callable([$service, $method])) {
                throw new ContainerException(
                    'Can not call method "'.$method.'" from class: "' . $className .'"'
                );
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
     * Resolve arguments
     *
     * @param array $params
     * @param array $definitionArgs
     * @return array
     * @throws ContainerException
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
                throw new ContainerException('Unable resolve parameter');
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

                throw new ContainerException('Unable resolve parameter');
            }

            $args[] = $value;
        }

        return $args;
    }
}
