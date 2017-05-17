<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Cache;

use Tlumx\Cache\FileCachePool;

class FileTest extends CacheTestCase
{
    protected $cacheDriver;

    protected $cacheDir;

    public function setUp()
    {
        $this->cacheDir = @tempnam(sys_get_temp_dir(), 'tlumxframework_tmp_cache');
        if (!$this->cacheDir) {
            $e = error_get_last();
            $this->fail("Can't create temporary cache directory-file: {$e['message']}");
        } elseif (!@unlink($this->cacheDir)) {
            $e = error_get_last();
            $this->fail("Can't remove temporary cache directory-file: {$e['message']}");
        } elseif (!@mkdir($this->cacheDir, 0777)) {
            $e = error_get_last();
            $this->fail("Can't create temporary cache directory: {$e['message']}");
        }
    }

    public function tearDown()
    {
        testRemoveDirTree($this->cacheDir);
        unset($this->cacheDriver);
    }

    protected function getCacheDriver()
    {
        if (!$this->cacheDriver) {
            $options = [
                'directory' => $this->cacheDir
            ];
            $this->cacheDriver = new FileCachePool($options);
        }

        return $this->cacheDriver;
    }

    public function testMissingOptionDirectory()
    {
        $this->setExpectedException('InvalidArgumentException', 'Missing option "directory"');
        $cache = new FileCachePool([]);
    }
}
