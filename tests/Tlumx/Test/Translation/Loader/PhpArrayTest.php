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

use Tlumx\Translation\Loader\PhpArray;

class PhpArrayTest extends \PHPUnit_Framework_TestCase
{
    protected $loaderResouceDir;
    
    protected function setUp()
    {
        $this->loaderResouceDir = dirname(__DIR__).DIRECTORY_SEPARATOR.
                'resources'.DIRECTORY_SEPARATOR .'loader'.DIRECTORY_SEPARATOR;
    }
    
    public function testImplements()
    {
        $loader = new PhpArray();
        $this->assertInstanceOf('Tlumx\Translation\Loader\LoaderInterface', $loader);
    }
    
    public function testInvalidFilename()
    {        
        $filename = $this->loaderResouceDir.'some.php';
        $this->setExpectedException('InvalidArgumentException', sprintf(
            'Could not open file %s for reading',$filename)
        );
        $loader = new PhpArray();
        $loader->load($filename);        
    }
    
    public function testInvalidReturnArray()
    {
        $filename = $this->loaderResouceDir.'InvalidPhpArrayTranslation.php';
        $messages = include $filename;
        $this->setExpectedException('InvalidArgumentException', sprintf(
            'Expected an array, but received %s',gettype($messages))
        );
        $loader = new PhpArray();
        $loader->load($filename);
    }
    
    public function testLoader()
    {
        $filename = $this->loaderResouceDir.'PhpArrayTranslation.php';
        $loader = new PhpArray();
        $messages = $loader->load($filename);
        $this->assertEquals($messages, array('hello'=>'hello'));        
    }
}