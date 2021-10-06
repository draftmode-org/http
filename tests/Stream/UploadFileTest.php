<?php
namespace Terrazza\Component\Http\Tests\Stream;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Terrazza\Component\Http\Stream\UploadedFile;

class UploadFileTest extends TestCase
{
    function testCreateFromResource() {
        $handler = fopen(__FILE__, "r");
        new UploadedFile($handler);
        $this->assertTrue(true);
    }

    function testMoveToFromFile() {
        $source                                     = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "upload.txt";
        $target                                     = dirname(__DIR__, 1). DIRECTORY_SEPARATOR . "upload.clone.txt";
        @unlink($source);
        @unlink($target);
        file_put_contents($source, "content");
        $upload                                     = new UploadedFile($source);
        $upload->moveTo($target);
        @unlink($target);
        $this->assertTrue($upload->isMoved());
    }

    function testMoveToFromStream() {
        $stream                                     = (new HttpStreamFactory)->createStream(str_repeat("x", UploadedFile::COPY_STREAM_BUFFER_SIZE * 2));
        $target                                     = dirname(__DIR__, 1). DIRECTORY_SEPARATOR . "upload.clone.txt";
        @unlink($target);
        $upload                                     = new UploadedFile($stream);
        $upload->moveTo($target);
        @unlink($target);
        $this->assertTrue($upload->isMoved());
    }

    function testCreateFromStream() {
        $stream                                     = (new HttpStreamFactory)->createStream("content");
        $upload                                     = new UploadedFile($stream);
        $this->assertEquals($stream, $upload->getStream());
    }

    function testCreateFromFile() {
        $upload                                     = new UploadedFile(__FILE__);
        $this->assertEquals([
            "text/x-php",
            __FILE__,
            filesize(__FILE__),
        ], [
            $upload->getClientMediaType(),
            $upload->getClientFilename(),
            $upload->getSize()
        ]);
    }

    function testCreateFromStreamFromFile() {
        $stream                                     = (new HttpStreamFactory)->createStreamFromFile(__FILE__);
        $upload                                     = new UploadedFile($stream);
        $this->assertEquals([
            "text/x-php",
            __FILE__,
            filesize(__FILE__)
        ], [
            $upload->getClientMediaType(),
            $upload->getClientFilename(),
            $upload->getSize(),
        ]);
    }

    function testConstructorNotOK() {
        $upload = new UploadedFile(__FILE__, 0, $error = 2);
        $this->assertEquals($error, $upload->getError());
    }

    // Exception
    function testFailureConstructorNoStringNoResource() {
        $this->expectException(InvalidArgumentException::class);
        new UploadedFile(12);
    }

    function testGetStreamErrorSet() {
        $upload = new UploadedFile(__FILE__, 0,2);
        $this->expectException(RuntimeException::class);
        $upload->getStream();
    }

    function testGetStreamIsModed() {
        $stream                                     = (new HttpStreamFactory)->createStream("content");
        $target                                     = dirname(__DIR__, 1). DIRECTORY_SEPARATOR . "upload.clone.txt";
        @unlink($target);
        $upload                                     = new UploadedFile($stream);
        $upload->moveTo($target);
        @unlink($target);
        $this->expectException(RuntimeException::class);
        $upload->getStream();
    }

    function testFailureConstructorInvalidErrorCode() {
        $this->expectException(InvalidArgumentException::class);
        new UploadedFile("12", 0, 9999);
    }

    function testFailureConstructorNotAFile() {
        $this->expectException(InvalidArgumentException::class);
        new UploadedFile("12");
    }

    function testMoveToNoTargetPath() {
        $stream = (new HttpStreamFactory)->createStream("content");
        $upload = new UploadedFile($stream);
        $this->expectException(InvalidArgumentException::class);
        $upload->moveTo("");
    }
}