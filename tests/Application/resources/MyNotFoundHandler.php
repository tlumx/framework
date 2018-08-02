<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application;

use Tlumx\Application\Handler\NotFoundHandlerInterface;
use Zend\Diactoros\Response;

class MyNotFoundHandler implements NotFoundHandlerInterface
{
    public function handle(array $allowedMethods = [])
    {
        $response = new Response();

        if ($allowedMethods) {
            $str = "Methods: ".implode(',', $allowedMethods);
        } else {
            $str = "404";
        }

        $response = $response->withStatus(404);
        $response->getBody()->write($str);
        $response->withHeader('Content-Type', 'text/html');
        return $response;
    }
}
