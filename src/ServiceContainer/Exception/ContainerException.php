<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\ServiceContainer\Exception;

use Psr\Container\ContainerExceptionInterface as PsrContainerException;

/**
 * Base interface representing a generic exception in a container.
 * PSR-11 implement the ContainerExceptionInterface.
 */
class ContainerException extends \RuntimeException implements PsrContainerException
{
}
