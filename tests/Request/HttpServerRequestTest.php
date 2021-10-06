<?php
namespace Terrazza\Component\Http\Tests\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpServerRequest;

class HttpServerRequestTest extends TestCase {

    function testLoad() {
        $request = new HttpServerRequest("GET", "https://www.google.at", [], null, "1.1", ["info" => $serverInfo="info"]);
        $this->assertEquals([
            null,
            $serverInfo,
            ["info" => $serverInfo],

            [],

            [],
            $cookieInfo = "cookieInfo",
            null,
            [$cookieKey = "cookie" => $cookieInfo],

            null,
            $parsedBody = ["a" => 1]
        ], [
            $request->getServerParam("hello"),
            $request->getServerParam("info"),
            $request->getServerParams(),

            $request->getUploadedFiles(),

            $request->getCookieParams(),
            $request->withCookieParams([$cookieKey => $cookieInfo])->getCookieParam($cookieKey),
            $request->withCookieParams([$cookieKey => $cookieInfo])->getCookieParam("hello"),
            $request->withCookieParams([$cookieKey => $cookieInfo])->getCookieParams(),

            $request->getParsedBody(),
            $request->withParsedBody($parsedBody)->getParsedBody()
        ]);
    }

    function testValidBody() {
        $body                                       = json_encode([
            "id"                                    => $id = 1
        ]);
        $httpRequest                                = new HttpServerRequest(
            "GET",
            "localhost", [
            "Content-Type" => "application/json"
        ],
            $body);
        $httpRequest->isValidBody();
        $this->assertTrue(true);
    }

    function testInValidJsonBody() {
        $httpRequest                                = new HttpServerRequest(
            "GET",
            "localhost", [
            "Content-Type" => "application/json"
        ],
            "test");
        $this->expectException(InvalidArgumentException::class);
        $httpRequest->isValidBody();
    }

    function testParsedBodyFailure() {
        $request = new HttpServerRequest("GET", "https://www.google.at");
        $this->expectException(InvalidArgumentException::class);
        $request->withParsedBody("hallo");
    }
}