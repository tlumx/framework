<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use Tlumx\Log\Writer\WriterInterface;

/**
 * Implementation of PSR Logger
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 */
class Logger extends AbstractLogger
{
    /**
     * Available log levels
     *
     * @var array
     */
    protected $levels = [
        LogLevel::EMERGENCY => 800,
        LogLevel::ALERT     => 700,
        LogLevel::CRITICAL  => 600,
        LogLevel::ERROR     => 500,
        LogLevel::WARNING   => 400,
        LogLevel::NOTICE    => 300,
        LogLevel::INFO      => 200,
        LogLevel::DEBUG     => 100,
    ];

    /**
     * Current logger level
     *
     * @var int
     */
    protected $level = null;

    /**
     * Messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Registered writers
     *
     * @var array
     */
    protected $writers = [];

    /**
     * Construct
     *
     * @param int $level
     * @throws InvalidArgumentException
     */
    public function __construct($level = LogLevel::DEBUG)
    {
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException('Level "'.$level.'" is not defined on Psr\\Log\\LogLevel');
        }
        $this->level = $level;
        register_shutdown_function([$this, 'shutdown'], true);
    }

    /**
     * Set logger level
     *
     * @param int $level
     * @throws InvalidArgumentException
     */
    public function setLevel($level)
    {
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException('Level "'.$level.'" is not defined on Psr\\Log\\LogLevel');
        }

        $this->level = $level;
    }

    /**
     * Get logger level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Add logger writer
     *
     * @param string $name
     * @param WriterInterface $writer
     * @param array $levels
     */
    public function addWriter($name, WriterInterface $writer, array $levels = [])
    {
        $this->writers[$name] = [
            'writer' => $writer,
            'levels' => $levels
        ];
    }

    /**
     * Remove writer from logger
     *
     * @param string $name
     * @throws InvalidArgumentException
     */
    public function removeWriter($name)
    {
        if (!isset($this->writers[$name])) {
            throw new InvalidArgumentException('writer not isset');
        }

        unset($this->writers[$name]);
    }

    /**
     * Get all logger writers
     *
     * @return array
     */
    public function getWriters()
    {
        return $this->writers;
    }

    /**
     * Shutdown all writers
     *
     * @return void
     */
    public function shutdown()
    {
        if (empty($this->messages)) {
            return;
        }

        foreach ($this->writers as $name => $writer) {
            if (!empty($writer['levels'])) {
                $messages = [];
                foreach ($this->messages as $message) {
                    if (in_array($message[1], $writer['levels'])) {
                        $messages[] = $message;
                    }
                }
                $writer['writer']->write($messages);
            } else {
                $writer['writer']->write($this->messages);
            }
        }
        $this->messages = [];
    }

    /**
     * Get log messages
     *
     * @return array
     */
    public function getLogMessages()
    {
        return $this->messages;
    }

    /**
     * Logs with an arbitrary level
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @throws InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($this->levels[$level])) {
            throw new InvalidArgumentException('Level "'.$level.'" is not defined on Psr\\Log\\LogLevel');
        }

        if ($this->levels[$this->level] > $this->levels[$level]) {
            return;
        }

        if (is_object($message) && !method_exists($message, '__toString')) {
            $message = '[object '.get_class($message).']';
        }

        $message = (string) $message;

        if (false !== strpos($message, '{')) {
            $replacements = [];
            foreach ($context as $key => $val) {
                if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, "__toString"))) {
                    $replacements['{'.$key.'}'] = $val;
                    continue;
                }

                $replacements['{'.$key.'}'] = '['.gettype($val).']';
            }
            $message = strtr($message, $replacements);
        }

        $this->messages[] = [new \DateTime(), $level, $this->levels[$level], $message];
    }
}
