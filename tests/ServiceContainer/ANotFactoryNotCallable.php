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

use Tlumx\ServiceContainer\ServiceContainer;

class ANotFactoryNotCallable
{
    protected $some;

    protected $c;

    public function __construct($some)
    {

    }

    public function getSome()
    {
        return $this->some;
    }

    public function setContainer(ServiceContainer $c)
    {
        $this->c = $c;
    }

    public function getContainer()
    {
        return $this->c;
    }
}
