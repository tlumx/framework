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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP response.
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Response extends Message implements ResponseInterface
{
    /**
     * Status codes and reason phrases
     *
     * @var array
     */
    protected $statusCodes = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unassigned426Upgrade Required',
        427 => 'Unassigned428Precondition Required',
        429 => 'Too Many Requests',
        430 => 'Unassigned',
        431 => 'Request Header Fields Too Large',
        //SERVER ERRORS
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required'
    );

    /**
     * Status code
     *
     * @var int
     */
    protected $status = 200;

    /**
     * Constructor
     *
     * @param StreamInterface $body
     * @param int $status
     * @param array $headers
     * @param string $httpVersion
     */
    public function __construct(StreamInterface $body = null, $status = 200, array $headers = [], $httpVersion = '1.1')
    {
        if($body === null) {
            $this->stream = new Stream(fopen('php://temp', 'r+'));
        } else {
            $this->stream = $body;
        }
        
        $this->setStatus($status);
        
        $this->setHeaders($headers);
        
        $this->protocol = $httpVersion;
    }

    /**
     * Set status code
     *
     * @param int $code
     * @throws \InvalidArgumentException
     */
    protected function setStatus($code)
    {
        $code = (int) $code;
        if(!$this->validStatus($code)) {
            throw new \InvalidArgumentException('Invalid status code passed');
        }
        
        $this->status = $code;
    }

    /**
     * Validate status code
     *
     * @param int $code
     * @return bool
     */
    public function validStatus($code)
    {
        return array_key_exists((int) $code, $this->statusCodes) ? true : false;
    }

    /**
     * Set response headers
     *
     * @param array $headers
     */
    protected function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            
            $key = $this->prepareHeader($name);
            
            $this->headers[$key] = [
                'name' => $name,
                'value' => $value
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if(!$this->validStatus($code)) {
            throw new \InvalidArgumentException('Invalid status code passed');
        }        
        
        $cloneInstance = clone $this;
        $cloneInstance->status = $code;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return isset($this->statusCodes[$this->status]) ? $this->statusCodes[$this->status] : '';
    }

    /**
     * Write data to the response body
     *
     * @param string $str
     * @return \Tlumx\Http\Response
     */
    public function write($str)
    {
        $this->getBody()->write($str);
        
        return $this;
    }

    /**
     * Is response is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return in_array($this->status, array(201, 204, 304));
    }

    /**
     * Is response is informational
     *
     * @return bool
     */
    public function isInformational()
    {
        return ($this->status >= 100 && $this->status < 200);
    }

    /**
     * Is response is OK
     *
     * @return bool
     */
    public function isOk()
    {
        return ($this->status === 200);
    }

    /**
     * Is response is successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return ($this->status >= 200 && $this->status < 300);
    }

    /**
     * Is response is redirection
     *
     * @return bool
     */
    public function isRedirection()
    {
        return ($this->status >= 300 && $this->status < 400);
    }

    /**
     * Is response is forbidden
     *
     * @return bool
     */     
    public function isForbidden()
    {
        return ($this->status === 403);
    }

    /**
     * Is response is not found
     *
     * @return bool
     */
    public function isNotFound()
    {
        return ($this->status === 404);
    }
    
    /**
     * Is response is client error
     *
     * @return bool
     */
    public function isClientError()
    {
        return ($this->status >= 400 && $this->status < 500);
    }

    /**
     * Is response is server error
     *
     * @return bool
     */
    public function isServerError()
    {
        return ($this->status >= 500 && $this->status < 600);
    }

    /**
     * Redirect
     *
     * @param string $url
     * @param int $status
     * @return \Tlumx\Http\Response
     * @throws \InvalidArgumentException
     */
    public function redirect($url, $status = 302)
    {
        if(!is_string($url)) {
            throw new \InvalidArgumentException('Url must be a string');
        }
        
        $status = (int) $status;
        if(!array_key_exists($status, $this->statusCodes) || ($status < 300) || ($status >= 308)) {
            throw new \InvalidArgumentException('Invalid status passed');
        }
        
        $this->status = $status;
        $key = $this->prepareHeader('Location');
        $this->headers[$key] = [
            'name' => 'Location',
            'value' => [$url]
        ];
        
        return $this;
    }

    /**
     * Write JSON data to the response
     *
     * @param mixed $data
     * @return \Tlumx\Http\Response
     */
    public function setJsonBody($data)
    {
        $body = $this->getBody();
        $body->rewind();
        $body->write(json_encode($data));
        $key = $this->prepareHeader('Content-Type');
        $this->headers[$key] = [
            'name' => 'Content-Type',
            'value' => ['application/json;charset=utf-8']
        ];
        
        return $this;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        $return = sprintf(
                "HTTP/%s %s %s\n",
                $this->protocol,
                $this->status,
                $this->statusCodes[$this->status]
        );
        
        foreach ($this->getHeaders() as $name => $values) {
            $return .=  $name . ": " . implode(", ", $values) . "\n";
        }
        
        $return .= "\n" . (string) $this->getBody();
        
        return $return;
    }        
}
