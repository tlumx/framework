<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test;

use Tlumx\View\TemplatesManager;

class TemplatesManagerTest extends \PHPUnit_Framework_TestCase
{    
    public function testTemplatesPath()
    {
        $tm = new TemplatesManager();        
        $this->assertEquals([], $tm->getTemplatePaths());
        $tm->setTemplatePaths(['a' => 'a-path', 'b' => 'b-path']);
        $this->assertEquals(['a' => 'a-path'.DIRECTORY_SEPARATOR, 'b' => 'b-path'.DIRECTORY_SEPARATOR],
                $tm->getTemplatePaths());
                        
        $tm->addTemplatePath('c', __DIR__);
        $this->assertEquals([
            'a' => 'a-path'.DIRECTORY_SEPARATOR,
            'b' => 'b-path'.DIRECTORY_SEPARATOR,
            'c' => __DIR__.DIRECTORY_SEPARATOR
            ], $tm->getTemplatePaths());
        $this->assertTrue($tm->hasTemplatePath('a'));
        $this->assertTrue($tm->hasTemplatePath('b'));
        $this->assertTrue($tm->hasTemplatePath('c'));
        $this->assertFalse($tm->hasTemplatePath('d'));
        $this->assertEquals(__DIR__.DIRECTORY_SEPARATOR, $tm->getTemplatePath('c'));        
        $tm->clearTemplatePaths();
        $this->assertFalse($tm->hasTemplatePath('a'));
        $this->assertFalse($tm->hasTemplatePath('b'));
        $this->assertFalse($tm->hasTemplatePath('c'));
        $this->assertEquals([], $tm->getTemplatePaths());
    }
    
    public function testInvalidTemplatePath()
    {
        $tm = new TemplatesManager();  
        $tm->addTemplatePath('c', 'invalid-path');
        $this->setExpectedException('RuntimeException', "Invalid template path with namespace \"c\"");
        $tm->getTemplatePath('c');
    }

    public function testInvalidTemplatePathNotIsset()
    {
        $tm = new TemplatesManager();  
        $this->setExpectedException('RuntimeException', "Path with namespace \"some\" is not exist");
        $tm->getTemplatePath('some');
    }    
    
    public function testTemplates()
    {
        $tm = new TemplatesManager();  
        $this->assertEquals([], $tm->getTemplateMap());
        $tm->setTemplateMap(['a'=>'a.phtml', 'b'=>'b.phtml']);        
        $this->assertEquals(['a'=>'a.phtml', 'b'=>'b.phtml'], $tm->getTemplateMap());
        $tm->addTemplate('c', __FILE__);
        $this->assertEquals(['a'=>'a.phtml', 'b'=>'b.phtml', 'c'=>__FILE__], $tm->getTemplateMap());
        $this->assertTrue($tm->hasTemplate('c'));
        $this->assertTrue($tm->hasTemplate('b'));
        $this->assertTrue($tm->hasTemplate('c'));
        $this->assertFalse($tm->hasTemplate('d'));        
        $this->assertEquals(__FILE__, $tm->getTemplate('c'));
        $tm->clearTemplateMap();
        $this->assertEquals([], $tm->getTemplateMap());
    }
    
    public function testInvalidTemplateFilename()
    {
        $tm = new TemplatesManager();  
        $tm->addTemplate('some', 'invalid-file');
        $this->setExpectedException('RuntimeException', "Invalid template filename with name \"some\"");
        $tm->getTemplate('some');
    }

    public function testInvalidTemplateNotIsset()
    {
        $tm = new TemplatesManager();  
        $name = 'some';
        $this->setExpectedException('RuntimeException', "Template with name \"".$name."\" is not exist");
        $tm->getTemplate($name);
    }                
}