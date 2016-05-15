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

use Tlumx\Handler\DefaultExceptionHandler;
use Tlumx\ServiceProvider;

class DefaultExceptionHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
       $_SERVER = array(
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        );        
    }
    
    public function testImplements()
    {
        $provider = new ServiceProvider();
        $handler = new DefaultExceptionHandler($provider);
        $this->assertInstanceOf('Tlumx\Handler\ExceptionHandlerInterface', $handler);
    }
    
    /**
     * @runInSeparateProcess
     * @dataProvider getErrorProvider
     */
    public function testHandle($class, $displayEexceptions, $message)
    {
        $provider = new ServiceProvider();
        $provider->setConfig('display_exceptions', $displayEexceptions);
        $handler = new DefaultExceptionHandler($provider);
        
        $e = new $class;
        $response = $handler->handle($e);
    
        if($displayEexceptions) {
            $body = sprintf("<h1>An error occurred</h1><h2>%s</h2><h3>Message</h3><p>%s</p><h3>Stack trace</h3><p>%s</p>",
                $message, $e->getMessage(), $e->getTraceAsString());
        } else {
            $body = sprintf("<h1>An error occurred</h1><h2>%s</h2>", $message);
        }
                
        $result = sprintf("<html><head><title>%s</title><style>body {font-family: Helvetica,Arial,sans-serif;font-size: 20px;line-height: 28px;padding:20px;}</style></head><body>%s</body></html>",
            'Tlumx application: '.$message, $body);
        
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        
        $body = $response->getBody();
        $body->rewind();
        
        $this->assertEquals($result, $body->getContents());
    }
    
    public function getErrorProvider()
    {
        return array(
            array('\RuntimeException', true, 'Internal Server Error'),
            array('\Exception', false, 'Internal Server Error')
        );
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testHandleTemplate()
    {        
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'error.phtml';

        $provider = new ServiceProvider();
        $provider->setConfig(array('templates' => array('template_error' => $file)));
        $handler = new DefaultExceptionHandler($provider);
        
        $e = new \Exception();
        $response = $handler->handle($e);
        
        $view = $provider->getView();
        $view->message = 'Internal Server Error';      
        $result = $view->renderFile($file);

        
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        
        $body = $response->getBody();
        $body->rewind();        
        $this->assertEquals($result, $body->getContents());
     }    
}