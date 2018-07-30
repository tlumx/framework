<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Test\Application;

use Tlumx\Application\Handler\ExceptionHandlerInterface;
use Zend\Diactoros\Response;

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