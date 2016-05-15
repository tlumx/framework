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

use Tlumx\Handler\ExceptionHandlerInterface;
use Tlumx\Http\Response;

class MyErrorHandler implements ExceptionHandlerInterface
{
    public function handle(\Exception $e)
    {
        $response = new Response();
        
        $str = "Error: " . $e->getMessage();
        
        $response = $response->withStatus(500);
        $response->getBody()->write($str);
        $response->withHeader('Content-Type', 'text/html');
        return $response;
    }
}