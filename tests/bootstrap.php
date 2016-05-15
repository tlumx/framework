<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */

ini_set('session.use_cookies', 0);
ini_set('session.cache_limiter', '');

$autoloader = require(__DIR__ . '/../vendor/autoload.php');
$autoloader->add('Tlumx\\Test', __DIR__);

require(__DIR__ . '/functions.php');