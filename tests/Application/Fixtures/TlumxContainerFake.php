<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application\Fixtures;

use Psr\Container\ContainerInterface;

class TlumxContainerFake implements ContainerInterface
{
    public function get($key)
    {
    }

    public function set($key, $value)
    {
    }

    public function has($key)
    {
    }

    public function setAlias($alias, $service)
    {
    }

    public function register($key, $value, $isShare = true)
    {
    }

    public function registerDefinition($key, $value, $isShare = true)
    {
    }
}
