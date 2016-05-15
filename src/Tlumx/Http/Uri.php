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

use Psr\Http\Message\UriInterface;

/**
 * Implementation of PSR HTTP Uri
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Uri implements UriInterface
{
    /**
     * Allowed scheme names to their ports
     *
     * @var array
     */
    protected $schemes = [
        'http' 	=> 80,
        'https' => 443
    ];

    /**
     * Uri scheme
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * Uri user info
     *
     * @var string
     */
    protected $userInfo = '';

    /**
     * Uri host
     *
     * @var string
     */
    protected $host = '';

    /**
     * Uri port
     *
     * @var int|null
     */
    protected $port;

    /**
     * Uri path
     *
     * @var string
     */
    protected $path = '';

    /**
     * Uri query string
     *
     * @var string
     */
    protected $query = '';

    /**
     * Uri fragment string
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * Constructor
     *
     * @param string|nulls $uri
     * @throws \InvalidArgumentException
     */
    public function __construct($uri = null)
    {
        if (is_string($uri)) {
            $components = @parse_url($uri);
            
            if ($components === false) {
                throw new \InvalidArgumentException("Malformed or unsupported URI.");
            }
            
            $scheme = '';
            if(isset($components['scheme'])) {
                $scheme = $this->filterScheme($components['scheme']);
            }
            $this->scheme = $scheme;
            
            $userInfo = isset($components['user']) ? $components['user'] : '';
            if(isset($components['pass'])) {
                $userInfo .= ':' . $components['pass'];
            }
            $this->userInfo = $userInfo;
            
            $this->host = isset($components['host']) ? $components['host'] : '';
            
            $this->port = isset($components['port'])
                    ? $this->filterPort($components['port']) : null;
            
            $this->path = isset($components['path']) 
                    ? $this->filterPath($components['path']) : '';
            
            $this->query = isset($components['query'])
                    ? $this->filterQueryOrFragment($components['query']) : '';
            
            $this->fragment = isset($components['fragment'])
                    ? $this->filterQueryOrFragment($components['fragment']) : '';            
        } elseif ($uri !== null) {
            throw new \InvalidArgumentException(
                    'URI passed to constructor must be a string or null'
            );
        }        
    }

    /**
     * Create new Uri from superglobal variable
     *
     * @return Uri
     */
    static public function createFromGlobals()
    {
        $uri = new static();
        
        $scheme = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'))
                ? 'https' : 'http';
        $uri = $uri->withScheme($scheme);
        
        $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
        $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
        $uri = $uri->withUserInfo($user, $pass);
        
        $host = array('');
        $host = explode(':', (isset($_SERVER['HTTP_HOST'])
                ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']));
        if(sizeof($host) == 1) {
                $uri = $uri->withHost($host[0]);
                $uri = $uri->withPort(($scheme == 'http') ? 80 : 443);
        } elseif(isset($_SERVER['SERVER_PORT'])) {
                $uri = $uri->withHost($host[0]);
                $uri = $uri->withPort((int) $_SERVER['SERVER_PORT']);
        }
        
        if (isset($_SERVER['HTTP_X_REWRITE_URL']) && stripos(PHP_OS, 'WIN') !== false) {
            // check this first so IIS will catch
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif(isset($_SERVER['IIS_WasUrlRewritten'])
                && $_SERVER['IIS_WasUrlRewritten'] == '1'
                && isset($_SERVER['UNENCODED_URL'])
                && $_SERVER['UNENCODED_URL'] != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            $requestUri = $_SERVER['UNENCODED_URL'];
        }elseif(isset($_SERVER['REQUEST_URI'])) {
            // Apache, IIS 6.0
            $requestUri = $_SERVER['REQUEST_URI'];
            // HTTP proxy reqs setup request uri with scheme and host [and port]
            // + the url path, only use url path
            $hostUri = $uri->getScheme().'://';
            if (('http' == $scheme && $uri->getPort() == 80) 
                    || ('https' == $scheme && $uri->getPort() == 443)) {
                $hostUri .= $uri->getHost();
            } else {
                $hostUri .= $uri->getHost().':'.$uri->getPort();
            }
            
            if (strpos($requestUri, $hostUri) === 0) {
                $requestUri = substr($requestUri, strlen($hostUri));
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            // IIS 5.0 , PHP as CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
                $requestUri .= '?'. $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUri = '';
        }
        
        $parts = explode('?', $requestUri, 2);
        $uri = $uri->withPath($parts[0]);
        
        if(isset($parts[1])) {
            $uri = $uri->withQuery($parts[1]);
        }
        
        return $uri;
    }
    
    /**
     * Filter the scheme.
     *
     * Check the Uri scheme is allowed.
     *
     * @param string $scheme
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function filterScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = str_replace('://', '', $scheme);
        
        if($scheme == '') {
            return '';
        }
        
        if(!array_key_exists($scheme, $this->schemes)) {
            throw new \InvalidArgumentException('Invalid scheme');
        }
        
        return $scheme;
    }

    /**
     * Filter the port
     *
     * @param int|null $port
     * @return int|null
     * @throws \InvalidArgumentException
     */
    protected function filterPort($port = null)
    {
        if(is_null($port)) {
            return $port;
        }
        
        $port = (int) $port;
        if(($port < 1) || ($port > 65535)) {
            throw new \InvalidArgumentException(
                    sprintf("Invalid port: %d. Must be null or int between 1 and 65535", $port)
            );
        }
        
        return $port;
    }

    /**
     * Filters the path
     *
     * @param string $path
     * @return string
     */
    protected function filterPath($path)
    {
        $path = preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
        
        if(strlen($path) === 0) {
            return $path;
        } elseif(substr($path, 0, 1) !== '/') {
            $path = '/'.$path;
        }
        
        return $path;
    }

    
    /**
     * Filter a query or a fragment.
     * 
     * @param string $value
     * @return string
     */
    protected function filterQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority()
    {
        if ($this->host == '') {
            return '';
        }
        
        $authority = $this->userInfo ? $this->userInfo . '@' . $this->host : $this->host;
        
        $port = '';
        if(array_key_exists($this->scheme, $this->schemes)) {
            $validPort = $this->schemes[$this->scheme];
            $port = ($validPort == $this->port) ? '' : ':'.$this->port;
        }
        
        return $authority . $port;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme)
    {
        $scheme = $this->filterScheme($scheme);
        
        $cloneInstance = clone $this;
        $cloneInstance->scheme = $scheme;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null)
    {
        $userInfo = $password ? $user.':'.$password : $user;
        
        $cloneInstance = clone $this;
        $cloneInstance->userInfo = $userInfo;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host)
    {
        $cloneInstance = clone $this;
        $cloneInstance->host = $host;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port)
    {
        $port = $this->filterPort($port);
        
        $cloneInstance = clone $this;
        $cloneInstance->port = $port;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('The path must be a string');
        }
        
        $path = $this->filterPath($path);
        
        $cloneInstance = clone $this;
        $cloneInstance->path = $path;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query)
    {
        if(!is_string($query)) {
            throw new \InvalidArgumentException('The query must be a string');
        }
        
        if (strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }
        $query = $this->filterQueryOrFragment($query);
        
        $cloneInstance = clone $this;
        $cloneInstance->query = $query;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment)
    {
        if(!is_string($fragment)) {
            throw new \InvalidArgumentException('The fragment must be a string');
        }
        
        if (strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }
        
        $cloneInstance = clone $this;
        $cloneInstance->fragment = $fragment;
        return $cloneInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $uri = '';
        
        $uri .= (!empty($this->scheme)) ? $this->scheme . '://' : '';
        
        $uri .= (!empty($this->getAuthority())) ? $this->getAuthority() : '';
        
        if(!empty($this->path)) {
            $uri .= ('/' !== substr($this->path, 0, 1))
                    ? '/' . $this->path : $this->path;
        }
        
        $uri .= (!empty($this->query)) ? '?' . $this->query : '';
        
        $uri .= (!empty($this->fragment)) ? '#' . $this->fragment : '';
        
        return $uri;
    }    
}