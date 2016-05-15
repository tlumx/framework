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

use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP stream
 *
 * @see http://www.php-fig.org/psr/psr-7/
 */
class Stream implements StreamInterface
{
    /**
     * Readable resource mode prefixes
     *
     * @var array
     */
    protected static $readableModePrefixes = ['r', '+'];

    /**
     * Writable resource mode prefixes
     *
     * @var array
     */
    protected static $writableModePrefixes = ['w', 'a', 'x', 'c', '+'];

    /**
     * Stream resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $isWritable;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $isReadable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $isSeekable;

    /**
     * The size of the stream
     *
     * @var int|null
     */
    protected $size;

    /**
     * Constructor
     *
     * Set a new stream resource to the instance.
     *
     * @param resource $stream Stream resource
     * @throws \InvalidArgumentException If argument is not a stream resource
     */
    public function __construct($stream)
    {
        if(!is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new \InvalidArgumentException(
                    'Passed argument should be a PHP stream resource'
            );
        }
        
        $this->stream = $stream;
        $meta = stream_get_meta_data($this->stream);
        $mode = $meta['mode'];
        
        // is readable
        $this->isWritable = false;
        foreach (self::$writableModePrefixes as $prefix) {
            if(strstr($mode, $prefix)) {
                $this->isWritable = true;
                break;
            }
        }
        
        // is writable
        $this->isReadable = false;
        foreach (self::$readableModePrefixes as $prefix) {
            if(strstr($mode, $prefix)) {
                $this->isReadable = true;
                break;
            }
        }
        
        // is seekable
        $this->isSeekable = $meta['seekable'];
        
        // size
        $stats = fstat($this->stream);
        $this->size = $stats['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try{
            $this->rewind();
            return $this->getContents();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {        
        $resource = $this->detach();
        if(is_resource($resource)) {
            fclose($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        $this->isSeekable = false;
        $this->isReadable = false;
        $this->isWritable = false;
        $this->size = null;
        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if(is_null($this->size) && is_resource($this->stream)) {
            $stats = fstat($this->stream);
            $this->size = $stats['size'];
        }
        
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if(is_resource($this->stream)) {
            if(($position = ftell($this->stream)) !== false) {
                return $position;
            }
        }
        
        throw new \RuntimeException('Unable tell position');
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        $result = true;
        
        if(is_resource($this->stream)) {
            $result = feof($this->stream);
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->isSeekable;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if($this->isSeekable() && fseek($this->stream, $offset, $whence) === 0) {
            return;
        }
        
        throw new \RuntimeException('Could not seeking within stream');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return $this->isWritable;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if(!$this->isWritable()) {
            throw new \RuntimeException('Not writable stream');
        }        
        
        if(($result = fwrite($this->stream, $string)) === false) {
            throw new \RuntimeException('Cannot writing to the stream');
        }
        
        $this->size = null;
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->isReadable;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if(!$this->isReadable()) {
            throw new \RuntimeException('Not readable stream');
        }
        
        if(($result = fread($this->stream, $length)) === false) {
            throw new \RuntimeException('Cannot reading from the stream');
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if(!$this->isReadable()) {
            throw new \RuntimeException('Stream should be readable');
        }
        
        if (($contents = stream_get_contents($this->stream)) === false) {
            throw new RuntimeException('Unable to read from stream');
        }
        
        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if(!$this->stream || is_null($key)) {
            return stream_get_meta_data($this->stream);
        }
        
        $meta = stream_get_meta_data($this->stream);
        
        return isset($meta[$key]) ? $meta[$key] : null;
    }
}