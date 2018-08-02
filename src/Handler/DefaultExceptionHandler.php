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

use Tlumx\Application\ServiceProvider;

/**
 * Default exception handler.
 */
class DefaultExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var \Tlumx\ServiceProvider
     */
    protected $provider;

    /**
     * Constructor
     *
     * @param ServiceProvider $provider
     */
    public function __construct(ServiceProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Handle exception
     *
     * @param \Exception $e
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(\Exception $e)
    {
        $response = $this->provider->getResponse();
        $response = $response->withStatus(500);
        $message = 'Internal Server Error';

        $view = $this->provider->getView();
        if ($this->provider->getConfig('display_exceptions')) {
            $view->exception = $e;
        }
        $view->message = $message;

        $config = $this->provider->getConfig();
        if (isset($config['templates']['template_error'])) {
            $result = $view->renderFile($config['templates']['template_error']);
        } else {
            if (isset($view->exception)) {
                $body = sprintf("<h1>An error occurred</h1><h2>%s</h2><h3>Message</h3><p>%s</p><h3>Stack trace</h3><p>%s</p>", $message, $e->getMessage(), $e->getTraceAsString());
            } else {
                $body = sprintf("<h1>An error occurred</h1><h2>%s</h2>", $message);
            }

            $result = sprintf("<html><head><title>%s</title><style>body {font-family: Helvetica,Arial,sans-serif;font-size: 20px;line-height: 28px;padding:20px;}</style></head><body>%s</body></html>", 'Tlumx application: '.$message, $body);
        }

        $response->getBody()->write($result);
        $response->withHeader('Content-Type', 'text/html');
        return $response;
    }
}
