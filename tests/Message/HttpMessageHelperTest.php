<?php
namespace Terrazza\Component\Http\Tests\Message;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\HttpMessageHelper;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

class HttpMessageHelperTest extends TestCase {
    function testCommon() {
        $helper                                     = new HttpMessageHelperTestTrait;
        $hKey                                       = "hKey";
        $hValue                                     = "hValue";
        $this->assertEquals([
            false,
            true,
            false,
            $hArray = ["a", "b"],

            $protocol = "1.0",
            $protocol,

            $stream                                 = (new HttpStreamFactory())->createStream("thisIsContent"),
            $stream
        ],[
            $helper->hasHeader($hKey),
            $helper->withHeader($hKey, $hValue)->hasHeader($hKey),
            $helper->withHeader($hKey, $hValue)->withoutHeader($hKey)->hasHeader($hKey),
            $helper->withHeader($hKey, $hArray)->getHeader($hKey),

            $helper->withProtocolVersion($protocol)->getProtocolVersion(),
            $helper->withProtocolVersion($protocol)->withProtocolVersion($protocol)->getProtocolVersion(),

            $helper->withBody($stream)->getBody(),
            $helper->withBody($stream)->withBody($stream)->getBody(),
        ]);
    }

    // Exception
    function testValidateHeaderArrayCount() {
        $helper                                     = new HttpMessageHelperTestTrait;
        $this->expectException(InvalidArgumentException::class);
        $helper->withHeader("key", []);
    }

    function testValidateHeaderValueNumeric() {
        $helper                                     = new HttpMessageHelperTestTrait;
        $this->expectException(InvalidArgumentException::class);
        $helper->withHeader("key", [1]);
    }

    function testValidateHeaderKeyNumeric() {
        $helper                                     = new HttpMessageHelperTestTrait;
        $this->expectException(InvalidArgumentException::class);
        $helper->withHeader(1, "1");
    }

    function testValidateHeaderKeyInvalidChar() {
        $helper                                     = new HttpMessageHelperTestTrait;
        $this->expectException(InvalidArgumentException::class);
        $helper->withHeader("/", "1");
    }
}

class HttpMessageHelperTestTrait {
    use HttpMessageHelper;
}