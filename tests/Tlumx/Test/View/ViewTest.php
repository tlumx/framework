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

use Tlumx\View\View;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function testImplements()
    {
        $view = new View();
        $this->assertInstanceOf('Tlumx\View\ViewInterface', $view);
    }     
    
    public function testGetSetData()
    {
        $view = new View();
        $this->assertEquals(array(), $view->getData());
        $view->setData(array('a'=>123, 'b'=>'abc'));
        $this->assertEquals(array('a'=>123, 'b'=>'abc'), $view->getData());
        $view->setData(array('a'=>1234, 'c'=>'c'));
        $this->assertEquals(array('a'=>1234, 'b'=>'abc', 'c'=>'c'), $view->getData());
        $this->assertEquals(1234, $view->a);
        $view->some = 'some-value';
        $this->assertEquals('some-value', $view->some);
        $this->assertEquals(null, $view->foo);
        $this->assertTrue(isset($view->a));
        $this->assertFalse(isset($view->aaa));
        unset($view->a);
        $this->assertFalse(isset($view->a));
    }    
    
    public function testTemplatesPath()
    {
        $view = new View();
        $this->assertEquals(null, $view->getTemplatesPath());
        $view->setTemplatesPath('a'.DIRECTORY_SEPARATOR.'b');
        $this->assertEquals('a'.DIRECTORY_SEPARATOR.'b', $view->getTemplatesPath());
        $view->setTemplatesPath('a'.DIRECTORY_SEPARATOR.'b'.DIRECTORY_SEPARATOR);
        $this->assertEquals('a'.DIRECTORY_SEPARATOR.'b', $view->getTemplatesPath());
    }    
    
    public function testWidget()
    {
        $view = new View();
        $view->registerWidget('hello', function (array $params = array()) {
            return "Hello, " . $params['name'] ."!";
        });
        $this->assertEquals("Hello, Tlumx!", $view->widget('hello', array('name' => 'Tlumx')));
    }
    
    public function testInvalidRegisterWidgetName()
    {
        $view = new View();
        $view->registerWidget('hello', function (array $params = array()) {
            echo "Hello, " . $params['name'] ." !";
        });
        $this->setExpectedException('InvalidArgumentException');
        $view->registerWidget('hello', function () {
            return 'hello';
        });
    }
    
    public function testInvalidWidgetName()
    {
        $view = new View();
        $this->setExpectedException('InvalidArgumentException');
        $view->widget('my');
    }    
    
    public function testRenderFile()
    {
        $view = new View();
        $view->name = 'Tlumx';        
        $file = @tempnam(sys_get_temp_dir(), 'tlumx_tmp_view_');                     
        file_put_contents($file, 'Hello, <?php echo $this->name; ?>!');
                
        $this->assertEquals("Hello, Tlumx!", $view->renderFile($file));
                
        unlink($file);                
    }
    
    public function testInvalidRender()
    {
        $view = new View();
        $filename = 'some_no_exist_file';
        $this->setExpectedException('RuntimeException', sprintf(
            'View cannot render file "%s" does not exist', DIRECTORY_SEPARATOR.$filename.'.phtml')
        );        
        $view->render($filename);
    }    
    
    public function testRender()
    {
        $view = new View();
        $view->name = 'Tlumx';

        $path = sys_get_temp_dir();
        $file = @tempnam($path, 'tlumx_tmp_view_');
        file_put_contents($file.'.phtml', 'Hello, <?php echo $this->name; ?>!');
        
        $view->setTemplatesPath($path);
        $template = ltrim(substr($file, strlen($path)), DIRECTORY_SEPARATOR);
    
        $this->assertEquals("Hello, Tlumx!", $view->render($template));
    
        unlink($file);
    }
    
    public function testDisplay()
    {
        $view = new View();
        $view->name = 'Tlumx';
    
        $path = sys_get_temp_dir();
        $file = @tempnam($path, 'tlumx_view_');
        file_put_contents($file.'.phtml', 'Hello, <?php echo $this->name; ?>!');
    
        $view->setTemplatesPath($path);
        $template = ltrim(substr($file, strlen($path)), DIRECTORY_SEPARATOR);        
        $view->display($template);
        $this->expectOutputString("Hello, Tlumx!");
            
        unlink($file);
    }
    
    public function testGetSetDoctype()
    {
        $view = new View();
        $this->assertEquals('<!DOCTYPE html>', $view->getDoctype());
        $view->setDoctype(View::DOCTYPE_HTML4_01_STRICT);
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertEquals($doctype, $view->getDoctype());        
    }
    
    public function testInvalidSetDoctype()
    {
        $view = new View();
        $doctype = 'invalid_doctype';
        $this->setExpectedException('InvalidArgumentException', sprintf(
            'Invalid Doctype "%s"', $doctype)
        );         
        $view->setDoctype($doctype);
    }
    
    public function testGetSetTitle()
    {
        $view = new View();
        $this->assertEquals('Tlumx framework!!!', $view->getTitle());        
        $view->setTitle('Test');
        $this->assertEquals('Test', $view->getTitle());
        $this->assertEquals('<title>Test</title>', $view->createTitle());                
    }
    
    public function  testGetSetMeta()
    {
        $view = new View();
        $this->assertEquals('', $view->getMeta());
        $view->setMeta();
        $expected = "<meta name=\"\" content=\"\">";
        $this->assertEquals($expected, $view->getMeta());
        $view->setMeta('charset', 'utf-8');
        $expected = $expected . "\n" . "<meta charset=\"utf-8\">";
        $this->assertEquals($expected, $view->getMeta());                
    }
    
    public function testInvalidTypeSetMeta()
    {
        $view = new View();
        $type = 'xyz';
        $this->setExpectedException('InvalidArgumentException', sprintf(
                'Invalid meta type "%s"',$type)
        );        
        $view->setMeta($type);        
    }
    
    public function testInvalidShemeSetMeta()
    {
        $view = new View();
        $this->setExpectedException('InvalidArgumentException', 'Modifier "scheme" not supported by HTML5');        
        $view->setMeta('name', 'value', '', ['scheme'=>'scheme']);
    }    
    
    public function testGetSetLink()
    {
        $view = new View();
        $link = $view->createLink(array('key1' => 'value1'));
        $this->assertEquals('<link key1="value1">', $link);
        $view->setDoctype(View::DOCTYPE_XHTML1_0_STRICT);
        $link = $view->createLink(array('key1' => 'value1'));
        $this->assertEquals('<link key1="value1"/>', $link);
        $view->setDoctype(View::DOCTYPE_HTML5);
        $view->setLink(array('key1' => 'value1'));
        $this->assertEquals('<link key1="value1">', $view->getLinks());
        $view->setLink(array('key2' => 'value2'));        
        $expected = "<link key1=\"value1\">\n<link key2=\"value2\">";
        $this->assertEquals($expected, $view->getLinks());                                
    }
    
    public function testGetSetIcon()
    {
        $view = new View();
        $this->assertEquals('', $view->getIcon());
        $view->setIcon('#', 'png');
        $this->assertEquals('<link rel="icon" type="image/png" href="#">', $view->getIcon());
        $view->setIcon('#', 'ico');
        $this->assertEquals('<link rel="shortcut icon" type="image/x-icon" href="#">', $view->getIcon());        
        $view->setIcon('#', 'invalid type');
        $this->assertEquals('', $view->getIcon());        
    }
    
    public function testGetSetStylesheets()
    {
        $view = new View();
        $this->assertEquals('', $view->getStylesheets());
        $view->setStylesheet('#');
        $this->assertEquals('<link href="#" rel="stylesheet" media="all">', $view->getStylesheets());
        $view->setStylesheet('#', 'some');
        $expected = '<link href="#" rel="stylesheet" media="all">';
        $expected .= "\n";
        $expected .= '<link href="#" rel="stylesheet" media="some">';
        $this->assertEquals($expected, $view->getStylesheets());
    }
    
    public function testGetSetPlainCss()
    {
        $view = new View();
        $this->assertEquals('<style type="text/css"></style>', $view->getPlainCss());
        $view->setPlainCss('some');
        $this->assertEquals('<style type="text/css">some</style>', $view->getPlainCss());
        $view->setPlainCss('some2');
        $this->assertEquals('<style type="text/css">some2</style>', $view->getPlainCss());        
    }
    
    public function testCreateScript()
    {
        $view = new View();
        $expected = '<script type="text/javascript">script</script>';
        $this->assertEquals($expected, $view->createScript('script', false));        
        $expected = '<script src="file.js" type="text/javascript"></script>';
        $this->assertEquals($expected, $view->createScript('file.js'));        
    }
    
    public function testGetSetHeadScript()
    {
        $view = new View();
        $this->assertEquals('', $view->getHeadScripts());
        
        $view->appendHeadScript('file.js');
        $view->appendHeadScript('script', false);
        $expected = "<script src=\"file.js\" type=\"text/javascript\"></script>\n";
        $expected .= "<script type=\"text/javascript\">script</script>";
        $this->assertEquals($expected, $view->getHeadScripts());

        $view->prependHeadScript('file2.js');
        $view->prependHeadScript('script2', false);        
        $expected2 = "<script type=\"text/javascript\">script2</script>\n";
        $expected2 .= "<script src=\"file2.js\" type=\"text/javascript\"></script>\n";
        $expected2 .= $expected;
        $this->assertEquals($expected2, $view->getHeadScripts());        
    }
    
    public function testGetSetAfterBodyScript()
    {
        $view = new View();
        $this->assertEquals('', $view->getAfterBodyScripts());
        
        $view->appendAfterBodyScript('file.js');
        $view->appendAfterBodyScript('script', false);
        $expected = "<script src=\"file.js\" type=\"text/javascript\"></script>\n";
        $expected .= "<script type=\"text/javascript\">script</script>";
        $this->assertEquals($expected, $view->getAfterBodyScripts());

        $view->prependAfterBodyScript('file2.js');
        $view->prependAfterBodyScript('script2', false);        
        $expected2 = "<script type=\"text/javascript\">script2</script>\n";
        $expected2 .= "<script src=\"file2.js\" type=\"text/javascript\"></script>\n";
        $expected2 .= $expected;
        $this->assertEquals($expected2, $view->getAfterBodyScripts());         
    }        
}