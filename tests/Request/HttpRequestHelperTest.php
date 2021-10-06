<?php
namespace Terrazza\Component\Http\Tests\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Request\HttpRequestHelper;

class HttpRequestHelperTest extends TestCase {

    function testSetters() {
        $c = new HttpRequestHelperTestTrait;
        $request = new HttpClientRequest("GET", "https://www.cnn.com");
        $this->assertEquals([
            $method = "GET",
            $uri = "www.google.at",
            $request->getUri(),
            $requestTarget = "what",
            $queryParams = [$queryParamK = "a" => $queryParamV = 12],
            $queryParamV,
            null,
            $queryParamV,
            null,
            null,
            $c->getAttributes(),
        ],[
            $c->withMethod($method)->getMethod(),
            $request->withUri(new Uri("https://".$uri))->getUri()->getHost(),
            $request->withUri($request->getUri())->getUri(),
            $c->withRequestTarget($requestTarget)->getRequestTarget(),
            $c->withQueryParams($queryParams)->getQueryParams(),
            $c->withQueryParams($queryParams)->getQueryParam($queryParamK),
            $c->withQueryParams($queryParams)->getQueryParam($queryParamV),
            $c->withAttribute($queryParamK, $queryParamV)->getAttribute($queryParamK),
            $c->withAttribute($queryParamK, $queryParamV)->getAttribute($queryParamV),
            $c->withAttribute($queryParamK, $queryParamV)->withoutAttribute($queryParamK)->getAttribute($queryParamK),
            $c->withoutAttribute($queryParamK)->getAttributes(),
        ]);
    }

    function testGetPort() {
        $request        = new HttpClientRequest("GET", "www.cnn.com:".($port=1112));
        $this->assertEquals($port, $request->getUri()->getPort());
    }

    function testBuildRequestTarget() {
        $queryParams    = [$queryParamK = "a" => $queryParamV = 12];
        $request        = new HttpClientRequest("GET", "https://www.cnn.com?".($qpK="ny")."=".($qpV=1));
        $this->assertEquals([
            "/?$qpK=$qpV",
            "/?$queryParamK=$queryParamV"
        ],[
            $request->getRequestTarget(),
            $request->withQueryParams($queryParams)->getRequestTarget()
        ]);
    }

    function testGetPathParams() {
        $request        = new HttpClientRequest("GET", "https://www.cnn.com/1234");
        $this->assertEquals([
            "1234",
            null,
            "yes",
            null
        ], [
            $request->getPathParam("/{id}", "id"),
            $request->getPathParam("/{id}", "xid"),
            $request->withUri(new Uri("https://www.cnn.com/yes?get=yes"))->getPathParam("/{id}", "id"),
            $request->withUri(new Uri("https://www.cnn.com"))->getPathParam("/{id}", "id")
        ]);
    }

    function testRequestWithoutHost() {
        $request        = new HttpClientRequest("GET", "1234");
        $this->assertEquals("", $request->getUri()->getHost());
    }

    function testBuildRequestTargetFailure() {
        $c = new HttpRequestHelperTestTrait;
        $this->expectException(InvalidArgumentException::class);
        $c->getRequestTarget();
    }

    function testMethodFailure() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpRequestHelperTestTrait)->withMethod(1);
    }

    function testRequestTargetFailure() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpRequestHelperTestTrait)->withRequestTarget("a b");
    }
}

class HttpRequestHelperTestTrait {
    use HttpRequestHelper;
}