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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP message
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Message implements MessageInterface
{
    /**
     * Protocol version
     *
     * @var string
     */
    protected $protocol = '1.1';

    /**
     * Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Body stream
     *
     * @var StreamInterface
     */
    protected $stream;

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        if ($this->protocol === $version) {
            return $this;
        }
        
        $cloneInstance = clone $this;
        $cloneInstance->protocol = $version;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        $result = [];
        foreach ($this->headers as $key => $header) {
            $result[$header['name']] = $header['value'];
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        $key = $this->prepareHeader($name);
        
        return isset($this->headers[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        $key = $this->prepareHeader($name);
        if(isset($this->headers[$key])) {
            return $this->headers[$key]['value'];
        }
        
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        $header = $this->getHeader($name);
        
        return implode(',', $header);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        
        $key = $this->prepareHeader($name);
        
        $cloneInstance = clone $this;
        $cloneInstance->headers[$key] = [
            'name' => $name,
            'value' => $value            
        ];
        
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        
        $key = $this->prepareHeader($name);
        
        $currValue = $this->getHeader($name);
        $passedValue = is_array($value) ? $value : [$value];
        $newValue = array_merge($currValue, array_values($passedValue));
        
        $cloneInstance = clone $this;
        $cloneInstance->headers[$key] = [
            'name' => $name,
            'value' => $newValue            
        ];
        
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        
        $key = $this->prepareHeader($name);
        
        $cloneInstance = clone $this;
        unset($cloneInstance->headers[$key]);
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        if ($body === $this->stream) {
            return $this;
        }
        
        $cloneInstance = clone $this;
        $cloneInstance->stream = $body;
        return $cloneInstance;
    }

    /**
     * Prepare header
     * 
     * @param string $header
     * @return string
     */
    protected function prepareHeader($header)
    {
        $key = str_replace(' ','-',ucwords(strtolower(str_replace(array('-', '_'), ' ', (string) $header))));
        if (substr($key,0,5) === "http-") {
            $key = substr($key,5);
        }
        
        return $key;
    }
}