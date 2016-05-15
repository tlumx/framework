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

use Tlumx\Http\UploadedFile;

class UploadedFileTest extends \PHPUnit_Framework_TestCase
{    
    protected $uploadFile;

    public function setUp()
    {
        $this->uploadFile = @tempnam(sys_get_temp_dir(), 'tlumxframework_http_upload_');
                
        $fh = fopen($this->uploadFile, "w");
        fwrite($fh, "some text.");
        fclose($fh);                
    }
    
    public function tearDown()
    {
        if (file_exists($this->uploadFile)) {
            unlink($this->uploadFile);
        }
    }
    
    public function testCreateObject()
    {
        $name = $this->uploadFile;
        $size = 10;
        $errorStatus = UPLOAD_ERR_OK;
        $clientFilename = 'some.txt';
        $clientMediaType = 'text/plain';
        
        $uploadedFile = new UploadedFile($name, $size, $errorStatus, $clientFilename, $clientMediaType);
        $this->assertInstanceOf('Psr\Http\Message\UploadedFileInterface', $uploadedFile);
        
        
        $this->assertEquals($size, $uploadedFile->getSize());
        $this->assertEquals($errorStatus, $uploadedFile->getError());
        $this->assertEquals($clientFilename, $uploadedFile->getClientFilename());
        $this->assertEquals($clientMediaType, $uploadedFile->getClientMediaType());        
    }
    
    public function testGetStream()
    {        
        $uploadedFile = new UploadedFile($this->uploadFile, 10, 0, 'some.txt', 'text/plain');
        $stream = $uploadedFile->getStream();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $stream->close();
    }
    
    public function testMoveTo()
    {
        $uploadedFile = new UploadedFile($this->uploadFile, 10, 0, 'some.txt', 'text/plain');                
        
        $newFilename = uniqid('tlumxframework_http_upload_file-');
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $newFilename;
        $uploadedFile->moveTo($path);
        
        $this->assertTrue(file_exists($path));
                                
        $contents = file_get_contents($path);
        $this->assertEquals('some text.', $contents);
        
        unlink($path);            
    }
    
    public function testErrorHasBeenMovedMoveTo()
    {
        $uploadedFile = new UploadedFile($this->uploadFile, 10, 0, 'some.txt', 'text/plain');                
        
        $newFilename = uniqid('tlumxframework_http_upload_file-');
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $newFilename;        
        $uploadedFile->moveTo($path);        
        $this->assertTrue(file_exists($path));                        
        unlink($path);            
        
        $this->setExpectedException('RuntimeException', 'Uploaded file has already been moved');
        $uploadedFile->moveTo($path);
    }    
    
    public function testErrorHasBeenMovedGetStream()
    {
        $uploadedFile = new UploadedFile($this->uploadFile, 10, 0, 'some.txt', 'text/plain');                
        
        $newFilename = uniqid('tlumxframework_http_upload_file-');
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $newFilename;        
        $uploadedFile->moveTo($path);        
        $this->assertFileExists($path);                
        unlink($path);            
        
        $this->setExpectedException(
                'RuntimeException', 
                'Cannot retrieve stream: "Uploaded file has already been moved"'
        );
        $uploadedFile->getStream();
    }    
    
    public function testCreateFromGlobal()
    {
        $_FILES = [
            'myfile' => [
                'name' => 'someimage.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/phpM0Jr9t',
                'error' => 0,
                'size' => 190414                                
            ]
        ];
        
        $myFile = new UploadedFile('/tmp/phpM0Jr9t', 190414, 0, 'someimage.jpg', 'image/jpeg');
        $expected = [
            'myfile' => $myFile
        ];
                        
        $uploadedFiles = UploadedFile::createFromGlobal();
        $this->assertEquals($expected, $uploadedFiles);
        
        $_FILES = [
            'files' => [
                'name' => [
                    0 => 'someimage1.jpg',
                    1 => 'someimage2.jpg'
                ],
                'type' => [
                    0 => 'image/jpeg',
                    1 => 'image/png'
                ],
                'tmp_name' => [
                    0 => '/tmp/phpM0Jr9t',
                    1 => '/tmp/phpMFKe87'
                ],
                'error' => [
                    0 => 0,
                    1 => 0
                ],
                'size' => [
                    0 => 190414,
                    1 => 12124
                ]
            ]
        ];        
        
        $expected = [
            'files' => [
                new UploadedFile('/tmp/phpM0Jr9t', 190414, 0, 'someimage1.jpg', 'image/jpeg'),
                new UploadedFile('/tmp/phpMFKe87', 12124, 0, 'someimage2.jpg', 'image/png')
            ]
        ];        
        
        $uploadedFiles = UploadedFile::createFromGlobal();
        $this->assertEquals($expected, $uploadedFiles);
    }    
}

