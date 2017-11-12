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
