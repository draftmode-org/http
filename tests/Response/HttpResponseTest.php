<?php
namespace Terrazza\Component\Http\Tests\Response;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Response\HttpResponse;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

class HttpResponseTest extends TestCase {

    function testCommon() {
        $response = new HttpResponse($status=200, [$headerK = "key" => $headerV = "value"], null, "1.1", $reason = "FINE");
        $this->assertEquals([
            $status,
            [$headerV],
            $reason,
        ],[
            $response->getStatusCode(),
            $response->getHeader($headerK),
            $response->getReasonPhrase()
        ]);
    }

    function testHeaderAsNumericArray() {
        $response = new HttpResponse(200, ["value1", "value2"]);
        $this->assertEquals(
            [
                ["value1"],
                ["value2"]
            ],
            $response->getHeaders(),
        );
    }

    function testResponseStream() {
        $responseStream     = (new HttpStreamFactory)->createStream("string");
        $response           = new HttpResponse(200, [], $responseStream);
        $this->assertEquals($responseStream, $response->getBody());
    }

    function testResponseString() {
        $responseString     = (new HttpStreamFactory)->createStream($content = "string");
        $response           = new HttpResponse(200, [], $content);
        $this->assertEquals($responseString, $response->getBody()->getContents());
    }

    function testResponseResource() {
        $streamFile         = __DIR__ . DIRECTORY_SEPARATOR . "ResponseFile.txt";
        $responseResource   = @fopen($streamFile, "r");
        $response           = new HttpResponse(200, [], $responseResource);
        $this->assertEquals((new HttpStreamFactory)->createStreamFromFile($streamFile)->getContents(), $response->getBody()->getContents());
    }

    function testStatusCodeRangeFailure() {
        $this->expectException(InvalidArgumentException::class);
        new HttpResponse(1);
    }

    function testStatusTypeFailure() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpResponse())->withStatus("failure");
    }
}