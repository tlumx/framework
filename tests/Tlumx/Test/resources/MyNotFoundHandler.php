<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test;

use Tlumx\Handler\NotFoundHandlerInterface;
use Tlumx\Http\Response;

class MyNotFoundHandler implements NotFoundHandlerInterface
{
    public function handle(array $allowedMethods = [])
    {
        $response = new Response();
        
        if($allowedMethods) {
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