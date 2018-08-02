<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Application\Handler;

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
