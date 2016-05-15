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

/**
 * Logger writer interface.
 */
interface WriterInterface
{
    /**
     * Write a message to the log storage.
     *
     * @param array $messages
     */
    public function write(array $messages);
}