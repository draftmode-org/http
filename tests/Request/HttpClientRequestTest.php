<?php
namespace Terrazza\Component\Http\Tests\Request;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpClientRequest;

class HttpClientRequestTest extends TestCase {
    function testQuery() {
        $request        = new HttpClientRequest("GET", new Uri("https://www.google.at?query=1"));
        $nRequest       = $request->withQueryParams(["new" => 1]);
        $this->assertEquals([
            ["query" => 1],
            "query=1",
            "/?query=1",

            ["new" => 1],
            "new=1",
            "/?new=1",
        ],[
            $request->getQueryParams(),
            $request->getUri()->getQuery(),
            $request->getRequestTarget(),

            $nRequest->getQueryParams(),
            $nRequest->getUri()->getQuery(),
            $nRequest->getRequestTarget(),
        ]);
    }
}