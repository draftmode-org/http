<?php
namespace Terrazza\Component\Http\Tests\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Request\HttpRequestMiddlewareMiddleware;
use Terrazza\Component\Http\Request\IHttpClientRequest;
use Terrazza\Component\Http\Request\IHttpRequestHandler;
use Terrazza\Component\Http\Request\IHttpRequestMiddleware;
use Terrazza\Component\Http\Response\HttpResponse;
use Terrazza\Component\Http\Response\IHttpResponse;

class HttpRequestFactoryTest extends TestCase {

    function testWithoutMiddleware() {
        $response           = new HttpResponse();
        $factory            = new HttpRequestMiddlewareMiddleware(
            new HttpRequestFactoryTestHttpClient($response)
        );
        $request            = new HttpClientRequest("GET", "https://www.google.com");
        $factoryResponse    = $factory->execute($request);
        $this->assertEquals([
            $response->getStatusCode()
        ],[
            $factoryResponse->getStatusCode()
        ]);
    }

    function testWithMiddleware() {
        $response           = new HttpResponse();
        $factory            = new HttpRequestMiddlewareMiddleware(
            new HttpRequestFactoryTestHttpClient($response),
            new HttpRequestFactoryTestMiddleware((new HttpResponse)->withStatus($modifiedStatusCode = 204))
        );
        $request            = new HttpClientRequest("GET", "https://www.google.com");
        $factoryResponse    = $factory->execute($request);
        $this->assertEquals([
            $modifiedStatusCode
        ],[
            $factoryResponse->getStatusCode()
        ]);
    }
}

class HttpRequestFactoryTestMiddleware implements IHttpRequestMiddleware {
    private IHttpResponse $_handle;
    public function __construct(IHttpResponse $response) {
        $this->_handle = $response;
    }

    public function handle(IHttpClientRequest $request, IHttpRequestHandler $requestHandler): IHttpResponse {
        return $this->_handle;
    }
}

class HttpRequestFactoryTestHttpClient implements ClientInterface {
    private IHttpResponse $_sendRequest;
    public function __construct(IHttpResponse $response) {
        $this->_sendRequest = $response;
    }
    public function sendRequest(RequestInterface $request): ResponseInterface {
        return $this->_sendRequest;
    }
}