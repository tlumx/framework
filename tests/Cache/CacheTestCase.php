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

abstract class CacheTestCase extends \PHPUnit_Framework_TestCase
{
    abstract protected function getCacheDriver();

    public function testImplements()
    {
        $this->assertInstanceOf("\\Psr\\Cache\\CacheItemPoolInterface", $this->getCacheDriver());
    }

    public function testGetItemInvalidKey()
    {
        try {
            $this->getCacheDriver()->getItem('');
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Psr\\Cache\\InvalidArgumentException", $e);
            $this->assertInstanceOf("\\Tlumx\\Cache\\Exception\\InvalidArgumentException", $e);
        }
    }

    public function testGetItem()
    {
        $item = $this->getCacheDriver()->getItem('key1');
        $this->assertInstanceOf("Psr\\Cache\\CacheItemInterface", $item);
        $this->assertEquals(null, $item->get());
    }

    public function testGetItemsInvalidKey()
    {
        try {
            $this->getCacheDriver()->getItems(['key1', '{key1}']);
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Psr\\Cache\\InvalidArgumentException", $e);
            $this->assertInstanceOf("\\Tlumx\\Cache\\Exception\\InvalidArgumentException", $e);
        }
    }

    public function testGetItems()
    {
        $items = $this->getCacheDriver()->getItems();
        $this->assertEquals([], $items);

        $items = $this->getCacheDriver()->getItems(['key1', 'key2', 'key3']);
        $this->assertEquals(3, count($items));
        foreach ($items as $item) {
            $this->assertInstanceOf("Psr\\Cache\\CacheItemInterface", $item);
            $this->assertEquals(null, $item->get());
        }
    }

    public function testHasItemInvalidKey()
    {
        try {
            $this->getCacheDriver()->hasItem('(key)');
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Psr\\Cache\\InvalidArgumentException", $e);
            $this->assertInstanceOf("\\Tlumx\\Cache\\Exception\\InvalidArgumentException", $e);
        }
    }

    public function testHasItem()
    {
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));

        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->getCacheDriver()->save($item);
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
    }

    public function testClear()
    {
        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->getCacheDriver()->save($item);
        $item = $this->getCacheDriver()->getItem('key2');
        $item->set('val2');
        $this->getCacheDriver()->save($item);
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
        $this->assertTrue($this->getCacheDriver()->hasItem('key2'));
        $this->assertTrue($this->getCacheDriver()->clear());
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));
        $this->assertFalse($this->getCacheDriver()->hasItem('key2'));
    }

    public function testDeleteItemInvalidKey()
    {
        try {
            $this->getCacheDriver()->deleteItem('@key');
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Psr\\Cache\\InvalidArgumentException", $e);
            $this->assertInstanceOf("\\Tlumx\\Cache\\Exception\\InvalidArgumentException", $e);
        }
    }

    public function testDeleteItem()
    {
        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->getCacheDriver()->save($item);
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
        $this->assertTrue($this->getCacheDriver()->deleteItem('key1'));
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));
    }

    public function testDeleteItemsInvalidKey()
    {
        try {
            $this->getCacheDriver()->deleteItems([':key', '{}()/\@:']);
        } catch (\Exception $e) {
            $this->assertInstanceOf("\\Psr\\Cache\\InvalidArgumentException", $e);
            $this->assertInstanceOf("\\Tlumx\\Cache\\Exception\\InvalidArgumentException", $e);
        }
    }

    public function testDeleteItems()
    {
        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->getCacheDriver()->save($item);
        $item = $this->getCacheDriver()->getItem('key2');
        $item->set('val2');
        $this->getCacheDriver()->save($item);
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
        $this->assertTrue($this->getCacheDriver()->hasItem('key2'));
        $this->assertTrue($this->getCacheDriver()->deleteItems(['key1', 'key2']));
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));
        $this->assertFalse($this->getCacheDriver()->hasItem('key2'));
    }

    public function testSave()
    {
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));
        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->getCacheDriver()->save($item);
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
    }

    public function testSaveDeferred()
    {
        $this->assertFalse($this->getCacheDriver()->hasItem('key1'));
        $item = $this->getCacheDriver()->getItem('key1');
        $item->set('val1');
        $this->assertTrue($this->getCacheDriver()->saveDeferred($item));
        $this->assertTrue($this->getCacheDriver()->hasItem('key1'));
    }

    public function testCommit()
    {
        $this->assertTrue($this->getCacheDriver()->commit());
    }
}
