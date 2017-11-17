<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\ServiceContainer;

use Tlumx\ServiceContainer\FactoryInterface;
use Psr\Container\ContainerInterface;

class MyFactory2 implements FactoryInterface
{
    public function __invoke(ContainerInterface $container)
    {
        $val = $container->has('a') ? $container->get('a') : 0;
        $val++;
        return $val++;
    }
}
