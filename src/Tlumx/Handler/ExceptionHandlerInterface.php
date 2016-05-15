<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Handler;

/**
 * Exception handler interface.
 */
interface ExceptionHandlerInterface
{
    /**
     * Handle exception
     *
     * @param \Exception $e
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Exception $e);
}