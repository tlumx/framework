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

use Tlumx\Translation\Loader\Po;

class PoTest extends \PHPUnit_Framework_TestCase
{
    protected $loaderResouceDir;

    protected function setUp()
    {
        $this->loaderResouceDir = dirname(__DIR__).DIRECTORY_SEPARATOR.
                'resources'.DIRECTORY_SEPARATOR .'loader'.DIRECTORY_SEPARATOR;
    }

    public function testImplements()
    {
        $loader = new Po();
        $this->assertInstanceOf('Tlumx\Translation\Loader\LoaderInterface', $loader);
    }

    public function testInvalidFilename()
    {
        $filename = $this->loaderResouceDir.'some.po';
        $this->setExpectedException('InvalidArgumentException', sprintf(
            'Could not open file %s for reading',
            $filename
        ));
        $loader = new Po();
        $loader->load($filename);
    }

    public function testLoader()
    {
        $filename = $this->loaderResouceDir.'PoTranslation.po';
        $loader = new Po();
        $messages = $loader->load($filename);
        $this->assertEquals($messages, ['hello' => 'hello','foo' => 'bar']);
    }
}
