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
 * Default not found handler.
 */
class DefaultNotFoundHandler implements NotFoundHandlerInterface
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
     * Handle
     *
     * @param array $allowedMethods
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(array $allowedMethods = [])
    {
        $response = $this->provider->getResponse();

        if (empty($allowedMethods)) {
            $response = $response->withStatus(404);
            $message = 'Page not found';
        } else {
            $response = $response->withStatus(405);
            $message = 'Method Not Allowed';
        }

        $config = $this->provider->getConfig();
        if (isset($config['templates']['template_404'])) {
            $view = $this->provider->getView();
            $view->message = $message;
            $result = $view->renderFile($config['templates']['template_404']);
        } else {
            $body = sprintf("<h1>An error occurred</h1><h2>%s</h2>", $message);
            $result = sprintf("<html><head><title>%s</title><style>body {font-family: Helvetica,Arial,sans-serif;font-size: 20px;line-height: 28px;padding:20px;}</style></head><body>%s</body></html>", 'Tlumx application: '.$message, $body);
        }

        $response->getBody()->write($result);
        $response->withHeader('Content-Type', 'text/html');
        return $response;
    }
}
