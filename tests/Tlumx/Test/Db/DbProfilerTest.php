<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Db;

use Tlumx\Db\DbProfiler;

class DbProfilerTest extends \PHPUnit_Framework_TestCase
{    
    public function testProfiler()
    {
        $profiler = new DbProfiler();
        $key = $profiler->start('some query');        
        usleep(1000);        
        $profiler->end($key);
        
        $profile = $profiler->getProfile($key);
        
        $this->assertEquals($profile['sql'],'some query');
        $this->assertEquals($profile['params'],null);
        $this->assertEquals($profile['total'],$profile['end']-$profile['start']);
        
        $key = $profiler->start('another query', array('a'=>100, 'b'=>'str'));
        usleep(1000);
        $profiler->end($key);
        
        $profile = $profiler->getProfile($key);
        
        $this->assertEquals($key, 1);
        $this->assertEquals(count($profiler->getProfiles()), 2);
        $this->assertEquals($profile['sql'], 'another query');
        $this->assertEquals($profile['params'], array('a'=>100, 'b'=>'str'));
        $this->assertEquals($profile['total'], $profile['end']-$profile['start']);
        
        $profiler->clear();
        $this->assertEquals(count($profiler->getProfiles()), 0);
    }
    
    public function testInvalidKey()
    {
        $profiler = new DbProfiler();
        
        $this->setExpectedException(
            'InvalidArgumentException', "Profiler has no query with handle '100'."
        );        
        $profiler->end(100);
        
        $this->setExpectedException(
            'InvalidArgumentException', "Profiler has no query with handle '500'."
        );
        $profiler->getProfile(500);
    }
}