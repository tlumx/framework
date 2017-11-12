<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Log\Writer;

use Tlumx\Log\Writer\WriterInterface;
use Psr\Log\LogLevel;

/**
 * Syslog writer.
 */
class Syslog implements WriterInterface
{
    /**
     * Log levels
     *
     * @var array
     */
    protected $logLevels = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG
    ];

    /**
     * Syslog identity
     *
     * @var string
     */
    protected $identity;

    /**
     * Syslog facility
     *
     * @var int
     */
    protected $facility;

    /**
     * Construnct
     *
     * @param string $identity
     * @param int $facility
     */
    public function __construct($identity = 'Tlumx\Log', $facility = LOG_USER)
    {
        $this->identity = $identity;
        $this->facility = $facility;
    }

    /**
     * Write a message to the log
     *
     * @param array $messages
     * @throws \RuntimeException
     */
    public function write(array $messages)
    {
        openlog($this->identity, LOG_PID, $this->facility);

        foreach ($messages as $message) {
            syslog($this->logLevels[$message[1]], $this->formatRecord($message));
        }

        closelog();
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
        return "<$level> $message";
    }
}
