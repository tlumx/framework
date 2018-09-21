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

use Tlumx\Application\Handler\DefaultNotFoundHandler;
use Tlumx\Application\DefaultContainerFactory;

class DefaultNotFoundHandlerTest extends \PHPUnit\Framework\TestCase
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
        $handler = new DefaultNotFoundHandler($this->container);
        $this->assertInstanceOf('Tlumx\Application\Handler\NotFoundHandlerInterface', $handler);
    }

    /**
     * @runInSeparateProcess
     * @dataProvider getDisplayEexceptions
     */
    public function testHandle(array $allowedMethods)
    {
        $handler = new DefaultNotFoundHandler($this->container);

        $response = $handler->handle($allowedMethods);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        if (!$allowedMethods) {
            $this->assertEquals(404, $response->getStatusCode());
            $message = 'Page not found';
        } else {
            $this->assertEquals(405, $response->getStatusCode());
            $message = 'Method Not Allowed';
        }

        $body = sprintf("<h1>An error occurred</h1><h2>%s</h2>", $message);
        $result = sprintf("<html><head><title>%s</title><style>body{font-family:Helvetica,Arial,sans-serif;".
            "font-size:20px;line-height:28px;padding:20px;}</style></head>".
            "<body>%s</body></html>", 'Tlumx application: '.$message, $body);
        $body = $response->getBody();
        $body->rewind();

        $this->assertEquals($result, $body->getContents());
    }

    public function getDisplayEexceptions()
    {
        return [
            [[]],
            [['GET']]
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
            'template_404' => $file
        ];
        $handler = new DefaultNotFoundHandler($this->container);

        $response = $handler->handle();
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(404, $response->getStatusCode());

        $view = $this->container->get('view');
        $view->message = 'Page not found';
        $result = $view->renderFile($file);

        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals($result, $body->getContents());
    }
}
