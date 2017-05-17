<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */

$autoloader = require dirname(__DIR__) . '/vendor/autoload.php';
$autoloader->addPsr4('Tlumx\Tests\\', __DIR__);

require dirname(__FILE__) . '/functions.php';
