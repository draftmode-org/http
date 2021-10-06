<?php
namespace Terrazza\Component\Http\Tests\Request;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpClientRequest;

class HttpClientRequestDeleteTest extends TestCase {

    function testMethodDelete() {
        $request = new HttpClientRequest($method = "DELETE", "https://" . $uri = "www.google.at");
        $this->assertEquals([
            ['Host' => [$uri]],
            $method,
            0
        ], [
            $request->getHeaders(),
            $request->getMethod(),
            $request->getBody()->getSize()
        ]);
    }

    function testMethodDeleteWithBody() {
        $request = new HttpClientRequest($method = "DELETE", "https://" . $uri = "www.google.at", [], $content = "content");
        $this->assertEquals([
            ['Host' => [$uri]],
            $method,
            strlen($content)
        ], [
            $request->getHeaders(),
            $request->getMethod(),
            $request->getBody()->getSize()
        ]);
    }
}