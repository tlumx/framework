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

use Psr\Http\Message\ResponseInterface;

/**
 * Not found handler interface.
 */
interface NotFoundHandlerInterface
{
    /**
     * Handle
     *
     * @param array $allowedMethods
     * @return ResponseInterface
     */
    public function handle(array $allowedMethods = []): ResponseInterface;
}
