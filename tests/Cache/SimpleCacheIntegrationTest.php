<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Cache;

use Cache\IntegrationTests\SimpleCacheTest;
use Tlumx\Cache\SimpleCache;
use Tlumx\Cache\FileCachePool;

class SimpleCacheIntegrationTest extends SimpleCacheTest
{
    protected static $cacheDir;

    public function createSimpleCache()
    {
        self::$cacheDir = @tempnam(sys_get_temp_dir(), 'tlumxframework_tmp_cache');
        if (!self::$cacheDir) {
            $e = error_get_last();
            $this->fail("Can't create temporary cache directory-file: {$e['message']}");
        } elseif (!@unlink(self::$cacheDir)) {
            $e = error_get_last();
            $this->fail("Can't remove temporary cache directory-file: {$e['message']}");
        } elseif (!@mkdir(self::$cacheDir, 0777)) {
            $e = error_get_last();
            $this->fail("Can't create temporary cache directory: {$e['message']}");
        }

        $options = [
            'directory' => self::$cacheDir
        ];
        $cachePool = new FileCachePool($options);

        return new SimpleCache($cachePool);
    }

    public static function tearDownAfterClass()
    {
        testRemoveDirTree(self::$cacheDir);
    }
}
