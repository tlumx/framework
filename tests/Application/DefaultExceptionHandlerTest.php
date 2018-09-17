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

use Tlumx\Application\Handler\DefaultExceptionHandler;
use Tlumx\Application\DefaultContainerFactory;
use Tlumx\ServiceContainer\ServiceContainer;

class DefaultExceptionHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected $container;

    public function setUp()
    {
        $_SERVER = [
            'SERVER_NAME'  => 'localhost',
            'SCRIPT_NAME' => 'index.php'
        ];
        $factory = new DefaultContainerFactory();
        $this->container = $factory->create([]);
    }

    public function testImplements()
    {
        $handler = new DefaultExceptionHandler($this->container);
        $this->assertInstanceOf('Tlumx\Application\Handler\ExceptionHandlerInterface', $handler);
    }

    /**
     * @runInSeparateProcess
     * @dataProvider getErrorProvider
     */
    public function testHandle($class, $displayEexceptions, $message)
    {
        $config = $this->container->get('config');
        $config['display_exceptions'] = $displayEexceptions;
        $handler = new DefaultExceptionHandler($this->container);

        $e = new $class;
        $response = $handler->handle($e);

        if ($displayEexceptions) {
            $body = sprintf(
                "<h1>An error occurred</h1><h2>%s</h2><h3>Message</h3>".
                "<p>%s</p><h3>Stack trace</h3><p>%s</p>",
                $message,
                $e->getMessage(),
                $e->getTraceAsString()
            );
        } else {
            $body = sprintf("<h1>An error occurred</h1><h2>%s</h2>", $message);
        }


        $result = sprintf(
            "<html><head><title>%s</title>".
            "<style>body{font-family:Helvetica,Arial,sans-serif;".
            "font-size:20px;line-height:28px;padding:20px;}</style></head><body>%s</body></html>",
            'Tlumx application: '. $message,
            $body
        );

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);

        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals($result, $body->getContents());
    }

    public function getErrorProvider()
    {
        return [
            ['\RuntimeException', true, 'Internal Server Error'],
            ['\Exception', false, 'Internal Server Error']
        ];
    }

    /**
     * @runInSeparateProcess
     */
    public function testHandleTemplate()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'error.phtml';

        $config = $this->container->get('config');
        $config['templates'] = [
            'template_error' => $file
        ];
        $handler = new DefaultExceptionHandler($this->container);

        $e = new \Exception();
        $response = $handler->handle($e);

        $view = $this->container->get('view');
        $view->message = 'Internal Server Error';
        $result = $view->renderFile($file);


        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);

        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals($result, $body->getContents());
    }
}
