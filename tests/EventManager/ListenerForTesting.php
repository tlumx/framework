<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\EventManager;

use Tlumx\EventManager\EventInterface;

class ListenerForTesting
{
    public static function attachListener(EventInterface $e)
    {
        $count = $e->getParam('count');
        $count++;
        $e->setParams(['count' => $count]);
    }

    public function __invoke(EventInterface $e)
    {
        $count = $e->getParam('count');
        $count++;
        $e->setParams(['count' => $count]);
    }
}
