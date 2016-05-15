<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Session;

use Tlumx\Session\Session;

class SessionTest extends \PHPUnit_Framework_TestCase
{    
    private $_session;
    
    public function setUp()
    {
        $this->_session = new Session();        
    }
    
    public function tearDown()
    {
        $this->_session->destroy();        
    }
    
    public function testGetOptions()
    {
        $options = $this->_session->getOptions();
        $this->assertArrayHasKey('name', $options);
        
        $option = $this->_session->getOptions('name');
        $this->assertEquals($option, ini_get('session.name'));
    }
    
    public function testInvalidGetOption()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->getOptions('invalid_option');
    }
    
    public function testSetOptions()
    {
        $this->_session->setOptions(array('name' => 'MySess','cookie_lifetime'=>0));
        $this->assertEquals('MySess', ini_get('session.name'));
        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
    }
    
    public function testInvalidSetOption()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->setOptions(array('invalid_option'=>'val'));
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testIsStarted()
    {
        $this->assertFalse($this->_session->isStarted());
        session_start();
        $this->assertTrue($this->_session->isStarted());
        session_destroy();
        $this->assertFalse($this->_session->isStarted());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testStart()
    {
        $this->assertFalse($this->_session->isStarted());
        $this->_session->start();
        $this->assertTrue($this->_session->isStarted());
        session_destroy();
        $this->assertFalse($this->_session->isStarted());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testClose()
    {
        $this->_session->start();
        $this->assertTrue($this->_session->isStarted());        
        $this->_session->close();
        $this->assertFalse($this->_session->isStarted());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testDestroy()
    {
        $this->_session->start();
        $_SESSION['key'] = 'value';
        $id = session_id();
        $this->assertTrue($this->_session->isStarted());
        $this->_session->destroy();                
        $this->assertFalse($this->_session->isStarted());
        $this->assertFalse(isset($_SESSION['key']));
        $this->assertFalse($id == session_id());
    }    
        
    /**
     * @runInSeparateProcess
     */
    public function testGetId()
    {
        $id = session_id();
        $this->assertEquals($id, $this->_session->getId());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetIdBeforeStart()
    {
        $this->setExpectedException('InvalidArgumentException');
        session_start();
        $this->_session->setId('my_id');
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testSetId()
    {        
        $this->_session->setId('my_id');
        $this->assertEquals('my_id', $this->_session->getId());
        $this->assertEquals('my_id', session_id());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testRegenerateID()
    {
        session_start();
        $id = session_id();
        $this->_session->regenerateID();
        $newId = session_id();
        
        $this->assertNotSame($id, $newId);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetIniSessionName()
    {
        $ini = ini_get('session.name');
        $this->assertEquals($ini, $this->_session->getName());
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetNameBeforeStart()
    {
        $this->setExpectedException('InvalidArgumentException');
        session_start();
        $this->_session->setName('MySess');
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetNameNotAlphanumeric()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->setName('MySess!');
    }
    
    /**
     * @runInSeparateProcess
     */
    public function testSetSessionName()
    {
        $this->_session->setName('MySess');
        $this->assertEquals('MySess', $this->_session->getName());
        $this->assertEquals('MySess', session_name());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRememberMe()
    {
        $this->_session->start();
        $id = session_id();
        $this->_session->rememberMe(1209600);
        $this->assertEquals(1209600, ini_get('session.cookie_lifetime'));
        $newId = session_id();
        $this->assertTrue($id != $newId);
    }

    public function testInvalidRememberMeSetLifetimeNotNumeric()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->rememberMe('some time');
    }
    
    public function testInvalidRememberMeSetLifetimeBadInteger()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->rememberMe(-1);
    }

    /**
     * @runInSeparateProcess
     */
    public function testForgetMe()
    {
        ini_set('session.cookie_lifetime', 1209600);
        $this->_session->forgetMe();                        
        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testGet()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->assertEquals($this->_session->get('key1'), 'value1');
        $this->assertEquals($this->_session->get('key2', false), false);
        $this->assertEquals($this->_session->get('key3', 'value'), 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetKeyString()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->set(123, 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testInvalidSetKeyUnderscore()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->set("_mykey", 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSet()
    {
        $this->_session->set('key1', 'value1');
        $this->assertArrayHasKey('key1', $_SESSION);
        $this->assertEquals('value1', $_SESSION['key1']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHas()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->assertTrue($this->_session->has('key1'));
    }	

    /**
     * @runInSeparateProcess
     */
    public function testRemove()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $this->_session->remove('key1');
        $this->assertEquals(false, isset($_SESSION['key1']));
    }	

    /**
     * @runInSeparateProcess
     */
    public function testRemoveAll()
    {
        session_start();
        $_SESSION['key1'] = 'value1';
        $_SESSION['key2'] = 'value2';
        $_SESSION['key3'] = 'value3';
        $this->_session->removeAll();
        $this->assertEquals(0, count($_SESSION));
    }

    public function testSetInvalidFlashKey()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->_session->flash(123, 'value');
    }

    /**
     * @runInSeparateProcess
     */
    public function testFlash()
    {
        $this->_session->flash('key1', 'value1');
        $this->_session->flash('key1', 'value2');
        $this->assertEquals('value2', $this->_session->flash('key1'));
        $this->assertEquals(null, $this->_session->flash('key1'));	    
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetAll()
    {
        $this->_session->set('key1', 'value1');
        $this->_session->set('key2', 'value2');
        $this->_session->set('key3', 'value3');	    
        $this->_session->flash('flash_key1', 'flash_value1');
        $this->_session->flash('flash_key2', 'flash_value2');

        $this->assertEquals(array('key1'=>'value1','key2'=>'value2','key3'=>'value3'), $this->_session->getAll());
    }
}