<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Translation;

use Tlumx\Translation\Loader\Mo;

class MoTest extends \PHPUnit_Framework_TestCase
{
    protected $loaderResouceDir;
    
    protected function setUp()
    {
        $this->loaderResouceDir = dirname(__DIR__).DIRECTORY_SEPARATOR.
                'resources'.DIRECTORY_SEPARATOR .'loader'.DIRECTORY_SEPARATOR;
    } 
    
    public function testImplements()
    {
        $loader = new Mo();
        $this->assertInstanceOf('Tlumx\Translation\Loader\LoaderInterface', $loader);
    }
    
    public function testInvalidFilename()
    {
        $filename = $this->loaderResouceDir.'some.mo';
        $this->setExpectedException('InvalidArgumentException', sprintf(
            'Could not open file %s for reading',$filename)
        );
        $loader = new Mo();
        $loader->load($filename);
    }
    
    public function testLoader()
    {
        $filename = $this->loaderResouceDir.'MoTranslation.mo';
        $loader = new Mo();
        $messages = $loader->load($filename);
        $this->assertEquals($messages, array('foo'=>'bar'));
    }
}