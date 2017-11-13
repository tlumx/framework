<?php
/**
 * Tlumx Framework (https://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2017 Yaroslav Kharitonchuk
 * @license   https://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Tests\Translation;

use Tlumx\Translation\Translator;
use Tlumx\Cache\FileCachePool;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
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
    }

    public function testTranslatorOptions()
    {
        $options = [];
        $translator = new Translator();

        $this->assertEquals($translator->getCacheItemPool(), null);
        $this->assertEquals($translator->getLanguage(), null);

        $options = [
            'directory' => $this->cacheDir
        ];
        $cache = new FileCachePool($options);

        $translator = new Translator([
            'language'  => 'en',
            'cache'     => $cache,
            'files'     => [
                ['csv', 'file1','en','default'],
                ['csv', 'file2','ua']
            ]
        ]);

        $this->assertEquals($translator->getCacheItemPool(), $cache);
        $this->assertEquals($translator->getLanguage(), 'en');
    }

    /**
     * Tests ClassLoader->loadClass()
     *
     * @param string $class
     * @dataProvider getTranslationProvider
     */
    public function testTranslate($message, $category, $language, $actual)
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'resources' .DIRECTORY_SEPARATOR;

        $translator = new Translator();
        $translator->setLanguage('en');
        $translator->addTranslationFile('array', $dir . 'en.php', 'en');
        $translator->addTranslationFile('array', $dir . 'en1.php', 'en', 'cat1');
        $translator->addTranslationFile('array', $dir . 'ua.php', 'ua');
        $translator->addTranslationFile('array', $dir . 'ua1.php', 'ua', 'cat1');

        $t = $translator->translate($message, $category, $language);
        $this->assertSame($t, $actual);
    }

    public function getTranslationProvider()
    {
        return [
            ["hello", 'default', 'en', 'hello'],
            ["hello", 'default', 'ua', 'привіт'],
            ["hello", 'cat1', 'en', 'hello1'],
            ["hello", 'cat1', 'ua', 'привіт1']
        ];
    }
}
