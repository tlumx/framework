<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Router;

/**
 * Router result class.
 */
class Result
{
    /**
     * Successful routing
     */
    const FOUND = 1;

    /**
     * Failure routing
     */
    const NOT_FOUND = 2;

    /**
     * Failure route HTTP method
     */
    const METHOD_NOT_ALLOWED = 3;

    /**
     * Routing result code
     *
     * @var int
     */
    protected $status;

    /**
     * The matched route name
     *
     * @var string
     */
    protected $name = '';

    /**
     * The matched route handler
     *
     * @var mixed
     */
    protected $handler = [];

    /**
     * The matched route parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * The matched route HTTP methods supported
     *
     * @var array
     */
    protected $allowedMethods = [];

    /**
     * The matched route middlewares
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Create successful router result
     * @param string $name
     * @param array $handler
     * @param array $params
     * @param array $middlewares
     * @return self
     */
    public static function createSuccessful($name, array $handler, array $params, array $middlewares)
    {
        $result = new self();
        $result->status = self::FOUND;
        $result->name = (string) $name;
        $result->handler = $handler;
        $result->params = $params;
        $result->middlewares = $middlewares;
        return $result;
    }

    /**
     * Create failure router result
     *
     * @return self
     */
    public static function createFailure()
    {
        $result = new self();
        $result->status = self::NOT_FOUND;
        return $result;
    }

    /**
     * Create failure method router result
     *
     * @param string $name
     * @param array $allowedMethods
     * @return self
     */
    public static function createFailureMethod($name, array $allowedMethods)
    {
        $result = new self();
        $result->status = self::METHOD_NOT_ALLOWED;
        $result->name = (string) $name;
        $result->allowedMethods = $allowedMethods;
        return $result;
    }

    /**
     * Returns whether the route is found
     *
     * @return bool
     */
    public function isFound()
    {
        return $this->status === self::FOUND;
    }

    /**
     * Returns whether the route is not found
     *
     * @return bool
     */
    public function isNotFound()
    {
        return !$this->isFound();
    }

    /**
     * Returns whether the route method not allowed
     *
     * @return bool
     */
    public function isMethodNotAllowed()
    {
        return $this->status === self::METHOD_NOT_ALLOWED;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route handler
     *
     * @return array
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Get route parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get route middlewares
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * Get route allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}