<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Cache\Exception;

use Psr\SimpleCache\CacheException as PsrSimpleCacheException;

class SimpleCacheException extends \Exception implements PsrSimpleCacheException
{

}
