<?php
namespace Terrazza\Component\Http\Tests\Request;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpRequestFactory;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

class HttpRequestFactoryTest extends TestCase {

    function testGetServerRequest() {
        $_SERVER["SERVER_PROTOCOL"]                 = "HTTP/1.1";
        $_SERVER["SERVER_ADDR"]                     = "www.example.com";
        $request                                    = (new HttpRequestFactory(new HttpStreamFactory()))->getServerRequest();
        $this->assertEquals("GET", $request->getMethod());
    }
}