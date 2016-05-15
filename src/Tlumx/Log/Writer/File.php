<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Log\Writer;

use Tlumx\Log\Writer\WriterInterface;

/**
 * File writer.
 */
class File implements WriterInterface
{
    /**
     * Directory to place log files in
     * 
     * @var string
     */
    protected $dir;

    /**
     * The file extention
     *
     * @var string
     */
    protected $fileExtension = '';

    /**
     * The log file name format
     *
     * @var string
     */
    protected $fileNameFormat = 'Y-m-d';

    /**
     * String of date format.
     *
     * @var string
     */
    protected $messageDateFormat = 'c';

    /**
     * Construnct
     *
     * @param string $dir Directory to place log files in
     * @param string $fileExtension The file extention
     * @throws \InvalidArgumentException
     */
    public function __construct($dir, $fileExtension = 'log')
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException('Directory for log files must be writable');
        }
        
        $this->dir = realpath($dir);
        $this->fileExtension = (string) $fileExtension;
    }

    /**
     * Write a message to the log
     *
     * @param array $messages
     * @throws \RuntimeException
     */
    public function write(array $messages)
    {
        $text = '';
        
        foreach ($messages as $message) {
            $text .= $this->formatRecord($message).PHP_EOL;
        }
        
        $filename = $this->dir.DIRECTORY_SEPARATOR.date($this->fileNameFormat);
        if($this->fileExtension) {
            $filename = $filename . '.' . $this->fileExtension;
        }
        
        if (!$handle = fopen($filename, 'a')) {
            throw new \RuntimeException("Cannot open or create log file");
        }
        
        if (fwrite($handle, $text) === false) {
            throw new \RuntimeException("cannot write to file");
        }
        
        fclose($handle);
    }

    /**
     * Formats records into a one-line string
     *
     * @param array $record
     * @return string
     */
    protected function formatRecord(array $record)
    {
        list($timestamp, $level, $levelCode, $message) = $record;
        return "[" . $timestamp->format($this->messageDateFormat) . "] $level ($levelCode): $message";
    }
}