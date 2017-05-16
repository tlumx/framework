<?php

/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */

function testRemoveDirTree($dir)
{ 
    if(!file_exists($dir)) {
        return;
    }
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
        (is_dir("$dir/$file")) ? testRemoveDirTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
}