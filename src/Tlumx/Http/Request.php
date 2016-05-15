<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Implementation of PSR HTTP request.
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Request extends Message implements RequestInterface
{
    /**
     * Allowed request methods
     *
     * @var array
     */
    protected $allowedMethods = [
        'CONNECT', 'DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT', 'TRACE',
    ];

    /**
     * Request method
     *
     * @var string
     */
    protected $method = '';

    /**
     * Request URI target
     *
     * @var null|string
     */
    protected $requestTarget;

    /**
     * Request URI
     * 
     * @var null|UriInterface
     */
    protected $uri;

    /**
     * Constructor
     *
     * @param string $method
     * @param UriInterface $uri
     * @param StreamInterface $body
     * @param array $headers
     */
    public function __construct($method, UriInterface $uri, StreamInterface $body, array $headers = [])
    {
        $this->method = $this->filterMethod($method);
        $this->uri = $uri;
        $this->stream = $body;
        $this->headers = [];
        foreach ($headers as $header => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            $key = $this->prepareHeader($header);
            $this->headers[$key] = [
                'name' => $header,
                'value' => $value
            ];
        }
        
        $host = $this->uri->getHost();
        $key = $this->prepareHeader('Host');
        if ($host && !isset($this->headers[$key])) {
            if ($port = $this->uri->getPort()) {
                $host .= ':' . $port;
            }
            $this->headers[$key] = [
                'name' => 'Host',
                'value' => [$host]
            ];
        }                 
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        if($this->requestTarget) {
            return $this->requestTarget;
        }
        
        $target = $this->uri->getPath();
        $target = $target ? $target : '/';
        $query = $this->uri->getQuery();
        $target .= $query ? '?'.$query : '';
        return $target;
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target');
        }
        
        $cloneInstance = clone $this;
        $cloneInstance->requestTarget = $requestTarget;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        $cloneInstance = clone $this;
        $cloneInstance->method = $this->filterMethod(strtoupper($method));
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $cloneInstance = clone $this;
        $cloneInstance->uri = $uri;
        
        if($preserveHost === false) {
            if($host = $uri->getHost()) {
                if ($port = $this->uri->getPort()) {
                    $host .= ':' . $port;
                }
                $this->headers['host'] = [
                    'name' => 'Host',
                    'value' => $host
                ];
            }
        }
        
        return $cloneInstance;
    }

    /**
     * Filter method, validate method
     *
     * @param string $method
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterMethod($method)
    {        
        if (!is_string($method)) {
            throw new \InvalidArgumentException('Unsupported HTTP method');
        }
        
        $method = strtoupper($method);
        
        if(!in_array($method, $this->allowedMethods)) {
            throw new \InvalidArgumentException('Unsupported HTTP method');
        }
        
        return $method;
    }        
}