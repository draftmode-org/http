<?php
namespace Terrazza\Component\Http\Tests\Response;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Response\HttpResponseFactory;
use UnexpectedValueException;

class HttpResponseFactoryTest extends TestCase {

    function testCreateResponse() {
        $response = (new HttpResponseFactory)->createResponse($code = 200, $reason="Fine");
        $this->assertEquals([
            $code,
            $reason
        ], [
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ]);
    }

    function testCreateJsonArray() {
        $response = (new HttpResponseFactory)->createJsonResponse(200, $data = [0 => "yes"]);
        $this->assertEquals([
            json_encode($data)
        ],[
            $response->getBody()->getContents()
        ]);
    }

    function testCreateJsonObjectSerializable() {
        $response = (new HttpResponseFactory)->createJsonResponse(200, new HttpResponseFactoryTestJsonObject);
        $this->assertEquals([
            json_encode(["test" => 1])
        ],[
            $response->getBody()->getContents()
        ]);
    }

    function testCreateJsonStringFailure() {
        $this->expectException(UnexpectedValueException::class);
        (new HttpResponseFactory)->createJsonResponse(200, "Fine");
    }

    function testCreateJsonObjectFailure() {
        $this->expectException(UnexpectedValueException::class);
        (new HttpResponseFactory)->createJsonResponse(200, new self);
    }
}

class HttpResponseFactoryTestJsonObject implements JsonSerializable {
    public int $test=1;
    public function jsonSerialize() {
        return $this;
    }
}