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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP ServerRequest.
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * Request cookie
     *
     * @var array
     */
    protected $cookieParams;

    /**
     * Server environment variables
     *
     * @var array
     */
    protected $serverParams;

    /**
     * Request query string params
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * List of uploaded files
     *
     * @var array UploadedFileInterface[]
     */
    protected $uploadedFiles;

    /**
     * Request body parsed
     *
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * Attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     *
     * @var array
     */
    protected $languages = [];

    /**
     * Constructor
     *
     * @param string $method
     * @param UriInterface $uri
     * @param StreamInterface $body
     * @param array $headers
     * @param array $serverParams
     * @param array $cookieParams
     * @param array $uploadedFiles
     */
    public function __construct(
            $method, 
            UriInterface $uri, 
            StreamInterface $body,
            array $headers = [], 
            array $serverParams = [], 
            array $cookieParams = [], 
            array $uploadedFiles = [])
    {
        parent::__construct($method, $uri, $body, $headers);
        
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * Create a request from the superglobal values
     *
     * @return ServerRequest
     */
    public static function createFromGlobal()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        
        $stream = fopen('php://input', 'r');
        $streamObject = new Stream($stream);
        
        $headers = [];
        if (function_exists('apache_request_headers')) {
            $headers = call_user_func('apache_request_headers');
        } else {
            foreach($_SERVER as $key=>$value) {
                if (substr($key,0,5) === "HTTP_") {
                    $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                    $headers[$key]=$value;
                } elseif (strncmp($key, 'CONTENT_', 8) === 0) {
                    $new_key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",$key))));
                    $headers[$new_key] = $_SERVER[$key];
                }
            }
            
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['AUTHORIZATION'] = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$pass);
            }
        }
        
        $request = new static(
                $method,
                Uri::createFromGlobals(),
                $streamObject,
                $headers,
                $_SERVER,
                $_COOKIE,
                UploadedFile::createFromGlobal()
        );
        
        return $request
                ->withQueryParams($_GET)
                ->withParsedBody($_POST);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $cloneInstance = clone $this;
        $cloneInstance->cookieParams = $cookies;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $cloneInstance = clone $this;
        $cloneInstance->queryParams = $query;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $cloneInstance = clone $this;
        $cloneInstance->uploadedFiles = $uploadedFiles;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $cloneInstance = clone $this;
        $cloneInstance->parsedBody = $data;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value)
    {
        $cloneInstance = clone $this;
        $cloneInstance->attributes[$name] = $value;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name)
    {
        $cloneInstance = clone $this;
        
        if(isset($this->attributes[$name])) {
            unset($cloneInstance->attributes[$name]);
        }
        
        return $cloneInstance;
    }

    /**
     * Is request method is ... ?
     *
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }

    /**
     * Is request method is CONNECT?
     *
     * @return bool
     */
    public function isConnect()
    {
        return $this->isMethod('CONNECT');
    }

    /**
     * Is request method is DELETE?
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is request method is GET?
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Is request method is HEAD?
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is request method is OPTIONS?
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is request method is PATCH?
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is request method is POST?
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Is request method is PUT?
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is request method is TRACE?
     *
     * @return bool
     */
    public function isTrace()
    {
        return $this->getMethod() === 'TRACE';
    }

    /**
     * Is request is XmlHttpRequest?
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Is request method is AJAX?
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->isXmlHttpRequest();
    }

    /**
     * Get request Referer URI
     *
     * @return \Tlumx\Http\Uri|null
     */
    public function getRefererUri()
    {
        $header = $this->getHeader('Referer');
        if($header) {
            return new Uri($header[0]);
        }
        
        return null;
    }

    /**
     * Get client ip
     *
     * @return string
     */
    public function getClientIp()
    {
        return isset($this->serverParams['REMOTE_ADDR']) ? $this->serverParams['REMOTE_ADDR'] : '';
    }

    /**
     * Get client host
     *
     * @return string
     */
    public function getClientHost()
    {
        return isset($this->serverParams['REMOTE_HOST']) ? $this->serverParams['REMOTE_HOST'] : '';
    }

    /**
     * Get User Agent
     *
     * @param mixed $default
     * @return mixed
     */
    public function getUserAgent($default = null)
    {
        $header = $this->getHeader('User-Agent');
        if(!$header) {
            return $default;
        }
        
        return $header[0];
    }

    /**
     * Set languages
     *
     * @param array $languages
     * @return \Tlumx\Http\ServerRequest
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
        return $this;
    }

    /**
     * Get languages
     *
     * @return array
     */
    public function getLanguages()
    {
        if($this->languages) {
            return $this->languages;
        }
        $languages = array();
        $header = $this->getHeader('Accept-Language');
        if(!$header) { 
            return $languages;
        }
        $header = $header[0];

        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                        $header, $matches);
        if (count($matches[1])) {
            foreach ($matches[1] as $key=>$value) {
                // from en-US to en_US
                $parts = explode('-', $value, 2);
                if(count($parts) >1) {
                    if($parts[0] == 'i') {
                        $lang = $parts[1];
                    } else {
                        $lang = strtolower($parts[0]) . '_' . strtoupper($parts[1]);
                    }
                } else {
                    $lang = $value;
                }
                
                if(isset($matches[4][$key]) && $matches[4][$key] !== '' ) {
                    $languages[$lang] = $matches[4][$key];
                } else {
                    $languages[$lang] = 1;
                }
            }
            
            arsort($languages, SORT_NUMERIC);
        }
        
        $this->languages = array_keys($languages);
        return  $this->languages;
    }

    /**
     * Get languages
     *
     * @param array $languages
     * @return array
     */
    public function getLanguage(array $languages = array())
    {
        $acceptLanguages = $this->getLanguages();
        if (count($languages) === 0) {
            return isset($acceptLanguages[0]) ? $acceptLanguages[0] : null;
        } elseif(count($acceptLanguages) === 0) {
            return $languages[0];
        }
        
        array_walk($acceptLanguages, function (&$val) {
            $val = strtolower(substr($val, 0, 2));
            return $val;
        });
        array_walk($languages, function (&$val) {
            $val = strtolower(substr($val, 0, 2));
            return $val;
        });
        
        $result = array_intersect($acceptLanguages, $languages);
        reset($result);
        return current($result) ? current($result) : $languages[0];
    }
}

