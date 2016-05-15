<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Translation\Loader;

/**
 * Po translation loader class.
 */
class Po implements LoaderInterface
{
    /**
     * Loads messages from an PO file
     *
     * @param string $filename
     * @return array
     * @throws \InvalidArgumentException
     */
    public function load($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException(sprintf('Could not open file %s for reading',$filename));
        }
        
        $content = implode('', file($filename));
        $count = preg_match_all('/msgid\s+((?:".*(?<!\\\\)"\s*)+)\s+' .
                        'msgstr\s+((?:".*(?<!\\\\)"\s*)+)/',
                        $content, $matches);
        $messages = array();
        for ($i = 0; $i < $count; $i++) {
            $key = substr(rtrim($matches[1][$i]), 1, -1);
            $value = substr(rtrim($matches[2][$i]), 1, -1);
            if($key) {
                $messages[$key] = $value;
            }
        }
        
        return $messages;
    }
}