<?php
namespace Terrazza\Component\Http\Tests\Request;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpClientRequest;

class HttpClientRequestGetTest extends TestCase {

    function testMethodGet() {
        $request = new HttpClientRequest($method = "GET", "https://" . $uri = "www.google.at");
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

    function testMethodGetWithBodyProtection() {
        $request = new HttpClientRequest($method = "GET", "https://" . $uri = "www.google.at", [], "content");
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
}