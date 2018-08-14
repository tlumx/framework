<?php
/**
 * Tlumx (https://tlumx.com/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016-2018 Yaroslav Kharitonchuk
 * @license   https://github.com/tlumx/framework/blob/master/LICENSE.md  (MIT License)
 */
namespace Tlumx\Tests\Application;

use Tlumx\Application\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testImplements()
    {
        $config = new Config();
        $this->assertInstanceOf(\ArrayAccess::class, $config);
        $this->assertInstanceOf(\Countable::class, $config);
        $this->assertInstanceOf(\IteratorAggregate::class, $config);
    }

    public function testArrayAccess()
    {
        $config = new Config();

        $this->assertEquals(false, isset($config['a']));
        $this->assertEquals(false, isset($config['b']));

        $config['a'] = 'a_val';
        $config['b'] = 'b_val';

        $this->assertEquals(true, isset($config['a']));
        $this->assertEquals(true, isset($config['b']));
        $this->assertEquals('a_val', $config['a']);
        $this->assertEquals('b_val', $config['b']);

        unset($config['a']);

        $this->assertEquals(false, isset($config['a']));
        $this->assertEquals(true, isset($config['b']));
        $this->assertEquals('b_val', $config['b']);
    }

    public function testCountable()
    {
        $config = new Config();
        $this->assertEquals(0, count($config));

        $config['a'] = 'v_a';
        $config['b'] = 'v_b';

        $this->assertEquals(2, count($config));

        unset($config['a']);

        $this->assertEquals(1, count($config));

        $config = new Config([
            'a' => 'a_v',
            'b' => 'b_v',
            'c' => 'c_v'
        ]);

        $this->assertEquals(3, count($config));

        $config['a'] = 'v_a';
        $config['d'] = 'v_d';

        $this->assertEquals(4, count($config));

        unset($config['b']);
        unset($config['c']);

        $this->assertEquals(2, count($config));
    }

    public function testIteratorAggregate()
    {
        $config = new Config([
            'a' => 'a_v',
            'b' => 'b_v',
            'c' => 'c_v'
        ]);

        $arrayIterator = $config->getIterator();
        $this->assertInstanceOf(\Traversable::class, $arrayIterator);

        foreach ($config as $key => $value) {
            $this->assertEquals($value, $config[$key]);
        }
    }

    public function testGetHasSetRemoveConfig()
    {
        $base = [
            'a' => 'a_v',
            'b' => 'b_v',
            'c' => 'c_v'
        ];
        $config = new Config($base);

        $this->assertEquals($base, $config->getAll());
        $this->assertTrue($config->has('a'));
        $this->assertTrue($config->has('b'));
        $this->assertTrue($config->has('c'));
        $this->assertFalse($config->has('d'));
        $this->assertEquals($base['a'], $config->get('a'));
        $this->assertEquals($base['b'], $config->get('b'));
        $this->assertEquals($base['c'], $config->get('c'));
        $this->assertEquals('d_v', $config->get('d', 'd_v'));
        $this->assertNull($config->get('d'));

        $config->remove('a');
        $config->remove('b');
        $config->set('d', 'd_v');
        $this->assertEquals([
            'c' => 'c_v',
            'd' => 'd_v'
        ], $config->getAll());
        $this->assertFalse($config->has('a'));
        $this->assertFalse($config->has('b'));
        $this->assertTrue($config->has('c'));
        $this->assertTrue($config->has('d'));
        $this->assertNull($config->get('a'));
        $this->assertNull($config->get('b'));
        $this->assertEquals($base['c'], $config->get('c'));
        $this->assertEquals('d_v', $config->get('d'));

        $config->removeAll();
        $this->assertEquals([], $config->getAll());
        $this->assertFalse($config->has('a'));
        $this->assertFalse($config->has('b'));
        $this->assertFalse($config->has('c'));
        $this->assertFalse($config->has('d'));
    }

    public function testMerge()
    {
        $a = [
            'key1' => 'val1_a',
            'key2' => [
                'val2_a_1',
                'val2_a_2'
            ],
            'key3' => 'key3_a',
            'key4' => 'key4_a'
        ];

        $b = [
            'key1' => 'val1_b',
            'key2' => [
                'val2_b_3',
                'val2_b_4',
                'val2_b_5',
            ],
            'key3' => ['val3_b_1', 'val3_b_2']
        ];



        $c = Config::merge($a, $b);

        $this->assertEquals([
            'key1' => 'val1_b',
            'key2' => [
                'val2_a_1',
                'val2_a_2',
                'val2_b_3',
                'val2_b_4',
                'val2_b_5'
            ],
            'key3' => ['val3_b_1', 'val3_b_2'],
            'key4' => 'key4_a'
        ], $c);
    }

    public function testMergeWith()
    {
        $config = new Config([
            'key1' => 'val1_a',
            'key2' => [
                'val2_a_1',
                'val2_a_2'
            ],
            'key3' => 'key3_a',
            'key4' => 'key4_a'
        ]);

        $config->mergeWith([
            'key1' => 'val1_b',
            'key2' => [
                'val2_b_3',
                'val2_b_4',
                'val2_b_5',
            ],
            'key3' => ['val3_b_1', 'val3_b_2']
        ]);

        $this->assertEquals([
            'key1' => 'val1_b',
            'key2' => [
                'val2_a_1',
                'val2_a_2',
                'val2_b_3',
                'val2_b_4',
                'val2_b_5'
            ],
            'key3' => ['val3_b_1', 'val3_b_2'],
            'key4' => 'key4_a'
        ], $config->getAll());
    }

    public function testMergeTo()
    {
        $config = new Config([
            'key1' => 'val1_a',
            'key2' => [
                'val2_a_1',
                'val2_a_2'
            ],
            'key3' => 'key3_a',
            'key4' => 'key4_a'
        ]);

        $config->mergeTo([
            'key1' => 'val1_b',
            'key2' => [
                'val2_b_3',
                'val2_b_4',
                'val2_b_5',
            ],
            'key3' => ['val3_b_1', 'val3_b_2']
        ]);

        $this->assertEquals([
            'key1' => 'val1_a',
            'key2' => [
                'val2_b_3',
                'val2_b_4',
                'val2_b_5',
                'val2_a_1',
                'val2_a_2'
            ],
            'key3' => 'key3_a',
            'key4' => 'key4_a'
        ], $config->getAll());
    }
}
