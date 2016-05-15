<?php
/**
 * Tlumx Framework (http://framework.tlumx.xyz/)
 *
 * @author    Yaroslav Kharitonchuk <yarik.proger@gmail.com>
 * @link      https://github.com/tlumx/framework
 * @copyright Copyright (c) 2016 Yaroslav Kharitonchuk
 * @license   http://framework.tlumx.xyz/license  (MIT License)
 */
namespace Tlumx\Test\Http;

use Tlumx\Http\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{    
    protected $streamObject;
    
    protected $stream;

    protected $mode;

    public function tearDown()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        
        $this->streamObject = null;
        $this->stream = null;
        $this->mode = null;
    }

    protected function getStreamObject($mode = 'r+')
    {
        if(!$this->streamObject) {
            $stream = fopen('php://temp', $mode);           
            $this->streamObject = new Stream($stream);
            $this->stream = $stream;            
            $this->mode = $mode;            
        } elseif ($mode !== $this->mode) {
            fclose($this->stream);
            $stream = fopen('php://temp', $mode);
            $this->streamObject = new Stream($stream);            
            $this->stream = $stream;
            $this->mode = $mode;        
        }        
        
        return $this->streamObject;
    }

    public function testImplements()
    {
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $this->getStreamObject());
    }    
    
    public function testToString()
    {
        $message = 'some message';
        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);        
        $streamObject = new Stream($stream);
        
        $this->assertEquals($message, (string) $streamObject);
        
        fclose($stream);
    }
    
    public function testClose()
    {
        $stream = fopen('php://temp', 'r+');
        $streamObject = new Stream($stream);
        $streamObject->close();
        $this->assertFalse(is_resource($stream));        
    }
    
    public function testDetach()
    {
        $streamObject = $this->getStreamObject();
        $streamObject->detach();
        
        $this->assertNull($streamObject->detach());
    }
    
    public function testDetachBeforeClose()
    {
        $stream = fopen('php://temp', 'r+');
        $streamObject = new Stream($stream);        
        $detachedReturn = $streamObject->detach();        
        $streamObject->close();
        
        $this->assertTrue(is_resource($detachedReturn));                
        $this->assertSame($stream, $detachedReturn);
    }
    
    public function testGetSize()
    {
        $message = 'some message';
        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);        
        $stats = fstat($stream);
        $size = $stats['size'];
        $streamObject = new Stream($stream);
        $this->assertEquals($size, $streamObject->getSize());  
        
        fclose($stream);
    }    
    
    public function testGet0Size()
    {
        $streamObject = $this->getStreamObject();
        $this->assertEquals(0, $streamObject->getSize());
    }
    
    public function testGetNullSize()
    {
        $streamObject = $this->getStreamObject();
        $streamObject->detach();
        $this->assertNull($streamObject->getSize());
    }    
    
    public function testTell()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        
        fseek($stream, 10);
        $this->assertEquals(10, $streamObject->tell());
        
        fclose($stream);
    }
    
    public function testErrorTell()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);        
        
        $this->setExpectedException('RuntimeException', 'Unable tell position');
        fseek($stream, 1000);
        $this->assertEquals(1000, $streamObject->tell());
        
        fclose($stream);
    }    
    
    public function testEof()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        
        fseek($stream, 2);
        $this->assertFalse($streamObject->eof());
        
        while (! feof($stream)) {
            fread($stream, 4096);
        }        
        
        $this->assertTrue($streamObject->eof());
        
        fclose($stream);
    }
    
    public function testEofWhenDetach()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        $streamObject->detach();       
        
        $this->assertTrue($streamObject->eof());
        
        fclose($stream);
    }    
    
    public function testTrueIsSeekable()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        
        $this->assertTrue($streamObject->isSeekable());
        
        fclose($stream);
    }
    
    public function testFalseIsSeekable()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        $streamObject->detach();
        
        $this->assertFalse($streamObject->isSeekable());
        
        fclose($stream);
    }    
    
    public function testSeek()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);
        rewind($stream);     
        $streamObject = new Stream($stream);
        $streamObject->seek(2);
        $return = stream_get_contents($stream);
        $this->assertEquals('me message', $return);
        
        fclose($stream);
    }
    
    public function testSeekError()
    {   
        $streamObject = $this->getStreamObject(); 
        $streamObject->detach();
        
        $this->setExpectedException('RuntimeException', 'Could not seeking within stream');
        
        $streamObject->seek(0);       
    }    
    
    public function testRewind()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $message);     
        $streamObject = new Stream($stream);
        $streamObject->seek(2);
        $streamObject->rewind();
        
        $this->assertEquals($message, (string) $streamObject);
        
        fclose($stream);        
    }    
    
    public function testIsReadableAndWritable()
    {
        // mode 'r+'
        $streamObject = $this->getStreamObject();
        $this->assertTrue($streamObject->isReadable());
        $this->assertTrue($streamObject->isWritable());
        // mode 'r'
        $streamObject = $this->getStreamObject('r');
        $this->assertTrue($streamObject->isReadable());
        $this->assertFalse($streamObject->isWritable());
    }
    
    public function testWriteErrorWithDetach()
    {   
        $streamObject = $this->getStreamObject(); 
        $streamObject->detach();
        
        $this->setExpectedException('RuntimeException');
        
        $streamObject->write('some');       
    }
    
    public function testWriteErrorOnNotWritable()
    {   
        $streamObject = $this->getStreamObject('r'); 
        
        $this->setExpectedException('RuntimeException', 'Not writable stream');
        
        $streamObject->write('some');       
    }    
    
    public function testWrite()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $message);     
        $streamObject = new Stream($stream);             
        $streamObject->write(' foo');
                
        $this->assertEquals('some message foo', (string) $streamObject);
        
        fclose($stream); 
    }
    
    public function testReadErrorWithDetach()
    {   
        $streamObject = $this->getStreamObject(); 
        $streamObject->detach();
        
        $this->setExpectedException('RuntimeException');
        
        $streamObject->read(2);       
    }
      
    
    public function testRead()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $message); 
        rewind($stream);
        $streamObject = new Stream($stream);             
        //$streamObject->
                
        $this->assertEquals('some', $streamObject->read(4));
        
        fclose($stream); 
    }    
    
    public function testGetContentsError()
    {
        $streamObject = $this->getStreamObject('x');
        $streamObject->getContents();
    }
    
    public function getContents()
    {
        $streamObject = $this->getStreamObject();
        $streamObject->write('some');
        $this->assertEquals('some', $streamObject->getContents());        
    }
    
    public function testGetMetadata()
    {
        $message = 'some message';        
        $stream = fopen('php://temp', 'w');
        fwrite($stream, $message); 
        rewind($stream);        
        $streamObject = new Stream($stream);        
        
        $result = stream_get_meta_data($stream);
        
        $this->assertEquals($result, $streamObject->getMetadata());
        
        $seekable = $result['seekable'];
        $this->assertEquals($seekable, $streamObject->getMetadata('seekable'));
        $this->assertNull($streamObject->getMetadata('invalid key'));
        
        fclose($stream); 
    }
}
