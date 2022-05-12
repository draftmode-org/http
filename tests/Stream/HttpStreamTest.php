<?php
namespace Terrazza\Component\Http\Tests\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\Http\Stream\HttpStream;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

class HttpStreamTest extends TestCase {
    function testCommon() {
        $newContent = "newContent";
        $stream = (new HttpStreamFactory())->createStream($content = "thisIsContent");
        $this->assertEquals([
            strlen($content),
            true,
            true,
            true,

            substr($content,0, $read = 4),
            substr($content, $read),
            strlen($newContent),
            10
        ],[
            $stream->getSize(),
            $stream->isReadable(),
            $stream->isWritable(),
            $stream->isSeekable(),

            $stream->read($read),
            $stream->getContents(),
            $stream->write($newContent),
            $stream->tell()
        ]);
    }

    function testGetContentAndEof() {
        $stream = (new HttpStreamFactory())->createStream($content = "thisIsContent");
        $stream->getContents();
        $this->assertEquals([
            $content,
            true,
        ],[
            $stream->getContents(false),
            $stream->eof()
        ]);
    }

    function testRewind() {
        $stream = (new HttpStreamFactory())->createStream($content = "thisIsContent");
        $stream->getContents();
        $stream->rewind();
        $this->assertEquals(
            $content,
            $stream->getContents()
        );
    }

    function testReadLength0() {
        $stream = (new HttpStreamFactory())->createStream("thisIsContent");
        $this->assertEquals("", $stream->read(0));
    }

    function testGetSize() {
        $handler                                    = fopen('php://temp', "r+");
        fwrite($handler, $content = "thisIsContent");
        fseek($handler, 0);
        $streamA                                    = new HttpStream($handler, ["size" => $size = strlen($content)]);
        $streamB                                    = (new HttpStreamFactory())->createStream("thisIsContent");
        $streamB->detach();
        $this->assertEquals([
            $size,
            null,
            []
        ], [
            $streamA->getSize(),
            $streamB->getSize(),
            $streamB->getMetadata()
        ]);
    }

    // Exceptions
    function testConstructor() {
        $this->expectException(InvalidArgumentException::class);
        new HttpStream("hello world");
    }
    function testCheckWriteable() {
        $stream = (new HttpStreamFactory())->createStreamFromFile(__FILE__, "r");
        $this->expectException(RuntimeException::class);
        $stream->write("hallo");
    }

    function testCheckStream() {
        $stream = (new HttpStreamFactory())->createStreamFromFile(__FILE__, "r");
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    function testReadLengthNegative() {
        $stream = (new HttpStreamFactory())->createStream("thisIsContent");
        $this->expectException(RuntimeException::class);
        $stream->read(-1);
    }

    function testSeek() {
        $stream = (new HttpStreamFactory())->createStream($content = "thisIsContent");
        $this->expectException(RuntimeException::class);
        $stream->seek(strlen($content) + 1);
    }

    function testCheckReadable() {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . "testWrite.txt";
        @unlink($filename);
        file_put_contents($filename, "data");
        $stream = (new HttpStreamFactory())->createStreamFromFile($filename, "w");
        @unlink($filename);
        $this->expectException(RuntimeException::class);
        $stream->read(2);
    }
}