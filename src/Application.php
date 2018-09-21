<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Application;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Tlumx Application class.
 */
class Application
{
    /**
     * Current framework version
     */
    const VERSION = '2.0';

    /**
     * @var array
     */
    protected $defaultConfig = [
        'error_reporting' => '-1',
        'display_errors' => '1',
        'display_exceptions' => true,
        'router_cache_enabled' => false,
        'router_cache_file' => 'routes.php.cache',
        'response_chunk_size' => 4096
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigureContainerInterface
     */
    protected $configureContainer;

    /**
     * Constructor.
     *
     * @param array|ContainerInterface $containerOrConfig congiguration array or implementation of
     *  ContainerInterface.
     * @param null|ConfigureContainerInterface $configureContainer implementation of
     *  ConfigureContainerInterface.
     * @throws \InvalidArgumentException if param $containerOrConfig is not configuration array
     *  or when it's not implement of ContainerInterface.
     */
    public function __construct($containerOrConfig, ConfigureContainerInterface $configureContainer = null)
    {
        if ($configureContainer !== null) {
            $this->setConfigureContainerObj($configureContainer);
        }

        if (is_array($containerOrConfig)) {
            $config = array_merge($this->defaultConfig, $containerOrConfig);
            $containerFactory = new DefaultContainerFactory();
            $this->container = $containerFactory->create($config, $this->getConfigureContainerObj());
        } elseif ($containerOrConfig instanceof ContainerInterface) {
            $this->container = $containerOrConfig;
        } else {
            throw new \InvalidArgumentException('Invalid input parameter "containerOrConfig": '.
                '"expected a ContainerInterface or congiguration array".');
        }
    }

    /**
     * Set ConfigureContainer object (implement ConfigureContainerInterface)
     *
     * @param ConfigureContainerInterface $configureContainer
     */
    public function setConfigureContainerObj(ConfigureContainerInterface $configureContainer)
    {
        $this->configureContainer = $configureContainer;
    }

    /*
     * Get ConfigureContainer object (implement ConfigureContainerInterface)
     *
     * @return ConfigureContainerInterface
     */
    public function getConfigureContainerObj()
    {
        if (!$this->configureContainer) {
            $this->configureContainer = new ConfigureTlumxContainer();
        }

        return $this->configureContainer;
    }

    /**
     * Get current application Container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get config object or single config option
     * Return current Config object if param "option" is null.
     * Return single config option - by param "option", or,
     *  if this option not exist - return "default" value.
     *
     * @param string|null $option
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($option = null, $default = null)
    {
        $config = $this->getContainer()->get('config');

        return ($option === null) ? $config : $config->get($option, $default);
    }

    /**
     * Create \ErrorExeption
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws \ErrorException
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() == 0) {
            return;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Run application bootstrappers
     *
     * @throws Exception\BootstrapperClassNotFoundException
     * @throws Exception\InvalidBootstrapperClassException
     */
    protected function bootstrap()
    {
        $bootstrappers = $this->getConfig('bootstrappers', []);

        foreach ($bootstrappers as $k => $class) {
            $class = (string) $class;
            if (!class_exists($class)) {
                throw new Exception\BootstrapperClassNotFoundException(
                    sprintf('Bootstrapper class "%s" not found', $class)
                );
            }

            $r = new \ReflectionClass($class);
            if (!$r->isSubclassOf('Tlumx\Application\Bootstrapper')) {
                throw new Exception\InvalidBootstrapperClassException(
                    sprintf(
                        "Bootstrapper class \"%s\" must extend from Tlumx\\Application\\Bootstrapper",
                        $class
                    )
                );
            }

            $bootstrap = new $class($this->getContainer(), $this->getConfigureContainerObj());
        }
    }

    /**
     * Run application
     *
     * @param bool $sendResponse
     * @return ResponseInterface
     */
    public function run($sendResponse = true)
    {
        error_reporting($this->getConfig('error_reporting'));
        ini_set('display_errors', $this->getConfig('display_errors'));
        set_error_handler([$this, 'errorHandler']);

        try {
            $this->bootstrap();

            $em = $this->getContainer()->get('event_manager');
            $event = new ApplicationEvent(ApplicationEvent::EVENT_POST_BOOTSTRAP);
            $event->setContainer($this->getContainer());
            $em->trigger($event);

            $middlewares = $this->getConfig('middlewares', []);
            $middlewares[] = 'RouteMiddleware';
            $middlewares[] = 'DispatchMiddleware';

            $default = function ($request) {
                $notFoundHandler = $this->getContainer()->get('not_found_handler');
                return $notFoundHandler->handle();
            };

            $handler = new RequestHandler($middlewares, $default, $this->getContainer());
            $response = $handler->handle($this->getContainer()->get('request'));
        } catch (\Exception $e) {
            if (ob_get_level() !== 0) {
                ob_end_clean();
            }

            $handler = $this->getContainer()->get('exception_handler');
            $response = $handler->handle($e);
        }

        if ($sendResponse === true) {
            $this->sendResponse($response);
        }

        return $response;
    }

    /**
     * Send the response to the client
     *
     * @param ResponseInterface $response
     */
    public function sendResponse(ResponseInterface $response)
    {
        if (headers_sent()) {
            return;
        }

        if (! $response->hasHeader('Content-Length') && (null !== $response->getBody()->getSize())) {
            $response = $response->withHeader('Content-Length', (string) $response->getBody()->getSize());
        }

        // Headers
        foreach ($response->getHeaders() as $header => $values) {
            $first = stripos($header, 'Set-Cookie') === 0 ? false : true;
            foreach ($values as $value) {
                header(sprintf('%s: %s', $header, $value), $first);
                $first = false;
            }
        }

        // Status Line
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // Body
        $body = $response->getBody();
        $body->rewind();

        $chunkSize = (int) $this->getConfig('response_chunk_size', 4096);

        while (!$body->eof()) {
            echo $body->read($chunkSize);
            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }
        }
    }
}
