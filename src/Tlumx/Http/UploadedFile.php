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

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP Uploaded Files
 * 
 * @see http://www.php-fig.org/psr/psr-7/
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * File
     *
     * @var string
     */
    protected $file;

    /**
     * Size in bytes
     *
     * @var int
     */
    protected $size;

    /**
     * PHP UPLOAD_ERR_xxx
     *
     * @var int
     */
    protected $error;

    /**
     * Filename sent by the client
     *
     * @var string
     */
    protected $filename;

    /**
     * Media type sent by the client
     *
     * @var string
     */
    protected $type;

    /**
     * Is uploaded file has already been moved
     *
     * @var bool
     */
    protected $moved = false;

    /**
     * Stream representing the uploaded file
     * 
     * @var StreamInterface
     */
    protected $stream;

    /**
     * Create from $_FILES
     *
     * @return UploadedFile
     */
    public static function createFromGlobal()
    {
        $globalFiles = $_FILES;
        $files = [];
        
        // helper function
        $parseOption = function (&$files, $values, $option) use (&$parseOption) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $parseOption($files[$key], $value, $option);
                } else {
                    $files[$key][$option] = $value;
                }
            }
        };
        
        foreach ($globalFiles as $fileKey => $fileOptions) {
            foreach ($fileOptions as $option => $value) {
                if (is_array($value)) {
                    $parseOption($files[$fileKey], $value, $option);
                } else {
                    $files[$fileKey][$option] = $value;
                }
            }
        }
        
        // create UploadedFile objects
        $createUploadedFiles = function(array $files) use (&$createUploadedFiles) {
            $objects = [];
            foreach ($files as $uploadedFileKey => $uploadedFileOption) {
                if (!isset($uploadedFileOption['error'])) {
                    if (is_array($uploadedFileOption)) {
                        $objects[$uploadedFileKey] = $createUploadedFiles($uploadedFileOption);
                    }
                    continue;
                }
                
                $objects[$uploadedFileKey] = new static(
                        $uploadedFileOption['tmp_name'],
                        isset($uploadedFileOption['size']) ? $uploadedFileOption['size'] : null,
                        $uploadedFileOption['error'],
                        isset($uploadedFileOption['tmp_name']) ? $uploadedFileOption['name'] : null,
                        isset($uploadedFileOption['type']) ? $uploadedFileOption['type'] : null
                    );
            }
            
            return $objects;
        };        
        
        return $createUploadedFiles($files);
    }

    /**
     * Constructor
     * 
     * @param string $file
     * @param int|null $size
     * @param int $error
     * @param string|null $filename
     * @param string|null $type
     */
    public function __construct($file, $size = null, $error = UPLOAD_ERR_OK, $filename = null, $type = null)
    {
        $this->file = $file;
        $this->size = $size;
        $this->error = $error;
        $this->filename = $filename;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getStream()
    {
        if($this->moved) {
            throw new \RuntimeException(
                    'Cannot retrieve stream: "Uploaded file has already been moved"'
            );
        }
        
        if(!($this->stream instanceof StreamInterface)) {
            $this->stream = new Stream(fopen($this->file, 'r'));
        }
        
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        if($this->moved) {
            throw new \RuntimeException('Uploaded file has already been moved');
        }
        
        if (!is_writable(dirname($targetPath))) {
            throw new \InvalidArgumentException(
                    'Path to which to move the uploaded file is not writable'
            );
        }
        
        if(empty(PHP_SAPI) || substr(PHP_SAPI, 0, 3) == 'cli') {
            // stream
            $handle = fopen($targetPath, 'wb+');
            if ($handle === false) {
                throw new \RuntimeException('Unable to write to target path');
            }
            
            $stream = $this->getStream();
            $stream->rewind();
            while (! $stream->eof()) {
                fwrite($handle, $stream->read(4096));
            }
            
            fclose($handle);            
        } else {
            //upload $_FILES
            if (!is_uploaded_file($this->file)) {
                throw new \RuntimeException('There was a problem with your upload');
            }
            
            if(!move_uploaded_file($this->file, $targetPath)) {
                throw new \RuntimeException('Failed to move uploaded file.');
            }
        }
        
        $this->moved = true;        
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename()
    {
        return $this->filename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->type;
    }        
}