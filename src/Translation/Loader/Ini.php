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
 * Ini translation loader class.
 */
class Ini implements LoaderInterface
{
    /**
     * Load translation messages from ini file
     *
     * @param string $filename
     * @return array
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function load($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('Could not open file %s for reading', $filename));
        }

        try {
            $messages = parse_ini_file($filename, false);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error reading INI file');
        }

        return $messages;
    }
}
