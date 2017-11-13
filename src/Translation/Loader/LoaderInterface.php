<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Translation\Loader;

/**
 * Translation loader interface.
 */
interface LoaderInterface
{
    /**
     * Load translation messages from file
     *
     * @param string $filename
     */
    public function load($filename);
}
