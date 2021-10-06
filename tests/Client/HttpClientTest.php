<?php
namespace Terrazza\Component\Http\Tests\Client;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Terrazza\Component\Http\Client\Exception\HttpClientException;
use Terrazza\Component\Http\Client\Exception\HttpClientRequestException;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Response\HttpResponse;
use Terrazza\Component\Http\Response\HttpResponseFactory;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Terrazza\Component\Http\Client\HttpClient;
use Terrazza\Component\Http\Client\Exception\HttpClientNetworkException;
use UnexpectedValueException;

class HttpClientTest extends TestCase {
    protected function getClient() : ClientInterface {
        return new HttpClient(
            new HttpResponseFactory(),
            new HttpStreamFactory()
        );
    }
    public function getClientTest() : HttpClientTestClass {
        return new HttpClientTestClass(
            new HttpResponseFactory(),
            new HttpStreamFactory()
        );
    }
    function testUriNotFound() {
        $client = $this->getClient();
        $this->expectException(HttpClientNetworkException::class);
        $client->sendRequest(
            new HttpClientRequest("GET", "x")
        );
    }

    function testUriBad() {
        $client = $this->getClient();
        $this->expectException(HttpClientRequestException::class);
        $client->sendRequest(
            new HttpClientRequest("PATCH", "https://www.examples.com/patch2invaliduri")
        );
        $this->assertTrue(true);
    }

    function testUriBadFetchRequest() {
        $client = $this->getClient();
        $eMethod = null;
        try {
            $client->sendRequest(
                new HttpClientRequest($rMethod = "PATCH", "https://www.examples.com/patch2invaliduri")
            );
        } catch (HttpClientException $exception) {
            $eMethod = $exception->getRequest()->getMethod();
        }
        $this->assertEquals($rMethod, $eMethod);
    }

    function testSuccess() {
        $client     = $this->getClientTest();
        $uri        = new Uri("http://user:pwd@localhost");
        $response   = $client->sendRequest(
            (new HttpClientRequest("GET", $uri, [], "content"))
            ->withProtocolVersion("1.2")
        );
        $response10 = $client->sendRequest(
            (new HttpClientRequest("GET", "localhost", [], "content"))
                ->withProtocolVersion("1.0")
        );
        $response11 = $client->sendRequest(
            (new HttpClientRequest("GET", "localhost", [], "content"))
                ->withProtocolVersion("1.1")
        );
        $response20 = $client->sendRequest(
            (new HttpClientRequest("GET", "localhost", [], "content"))
                ->withProtocolVersion("2.0")
        );
        $this->assertEquals([
            200,
            200,
            200,
            200
        ], [
            $response->getStatusCode(),
            $response10->getStatusCode(),
            $response11->getStatusCode(),
            $response20->getStatusCode(),
        ]);
    }

    function testPostLargeBody() {
        $client             = $this->getClient();
        $client->sendRequest(
            (new HttpClientRequest("POST", "localhost", [], str_repeat("text", 1024 * 1024)))
        );
        $this->assertTrue(true);
    }

    function testGetProtocolUnexpectedValue() {
        $client             = $this->getClientTest();
        $client->setProtocolVersionException();
        $this->expectException(HttpClientRequestException::class);
        $client->sendRequest(
            (new HttpClientRequest("GET", "localhost", [], "content"))
                ->withProtocolVersion("2.0")
        );
    }

    function testAddRequestBodyOptions() {
        $client             = $this->getClientTest();
        $request            = (new HttpClientRequest("POST", "localhost", [], "content"));
        $client->addRequestBodyOptions($request, []);
        $this->assertTrue(true);
    }

    function testAddRequestBodyOptionsHead() {
        $client             = $this->getClientTest();
        $request            = (new HttpClientRequest("HEAD", "localhost", [], "content"));
        $client->addRequestBodyOptions($request, []);
        $this->assertTrue(true);
    }

    function testHeaderExpect() {
        $client             = $this->getClient();
        $client->sendRequest(
            (new HttpClientRequest("POST", "localhost", ["expect" => "1"], "text"))
        );
        $this->assertTrue(true);
    }

    function testHeaderContentLength() {
        $client             = $this->getClient();
        $client->sendRequest(
            (new HttpClientRequest("POST", "localhost", ["Content-Length" => (string)strlen($text = "text")], $text))
        );
        $this->assertTrue(true);
    }

    function testPostNoContent() {
        $client             = $this->getClient();
        $client->sendRequest(
            (new HttpClientRequest("POST", "localhost", ["Content-Length" => "1"]))
        );
        $this->assertTrue(true);
    }

    function testGetHeaderFunctionSuccessful() {
        $response           = (new HttpResponse(200, []));
        $headerKey          = "htestkey";
        $headerValue        = "htestvalue";
        $responseKeyEmpty   = $response->getHeaderLine($headerKey);
        $client             = $this->getClientTest();
        $callback           = $client->getHeaderFunction($response);
        $headerLen          = $callback("", $hStr = "$headerKey:$headerValue");
        $responseKeySet     = $response->getHeaderLine($headerKey);
        $this->assertEquals([
            strlen($hStr),
            "",
            $headerValue
        ],[
            $headerLen,
            $responseKeyEmpty,
            $responseKeySet,
        ]);
    }

    function testGetHeaderFunctionSuccessfulAdd() {
        $headerKey          = "htestkey";
        $headerValue1       = "htestvalue1";
        $headerValue2       = "htestvalue2";
        $response           = (new HttpResponse(200, [$headerKey => $headerValue1]));
        $responseKey1       = $response->getHeaderLine($headerKey);
        $client             = $this->getClientTest();
        $callback           = $client->getHeaderFunction($response);
        $headerLen          = $callback("", $hStr = "$headerKey:$headerValue2");
        $responseKey2       = $response->getHeaderLine($headerKey);
        $this->assertEquals([
            strlen($hStr),
            $headerValue1,
            $headerValue1.", ".$headerValue2
        ],[
            $headerLen,
            $responseKey1,
            $responseKey2,
        ]);
    }

    function testGetHeaderFunctionFailureNotHttp() {
        $response           = (new HttpResponse(200, []));
        $client             = $this->getClientTest();
        $callback           = $client->getHeaderFunction($response);
        $this->expectException(InvalidArgumentException::class);
        $callback("", "data");
    }

    function testGetHeaderFunctionFailureHttp() {
        $response           = (new HttpResponse(200, []));
        $client             = $this->getClientTest();
        $callback           = $client->getHeaderFunction($response);
        $this->expectException(InvalidArgumentException::class);
        $callback("", "http/");
    }
}

class HttpClientTestClass extends HttpClient {
    private bool $protocolVersionException=false;

    public function prepareRequestOptions(RequestInterface $request, callable $headerFunction, callable $bodyFunction): array {
        return parent::prepareRequestOptions($request, $headerFunction, $bodyFunction);
    }
    public function getHeaderFunction(ResponseInterface &$response): callable{
        return parent::getHeaderFunction($response);
    }
    public function getBodyFunction(ResponseInterface $response): callable {
        return parent::getBodyFunction($response);
    }
    private ?ResponseInterface $response=null;
    public function setResponse(ResponseInterface $response) : void {
        $this->response = $response;
    }
    public function initResponse(): ResponseInterface {
        return $this->response ?? parent::initResponse();
    }
    public function setProtocolVersionException() {
        $this->protocolVersionException = true;
    }
    public function getProtocolVersion(string $requestVersion): int {
        if ($this->protocolVersionException) {
            throw new UnexpectedValueException("message");
        } else {
            return parent::getProtocolVersion($requestVersion);
        }
    }
    public function addRequestBodyOptions(RequestInterface $request, array $curlOptions): array {
        return parent::addRequestBodyOptions($request, $curlOptions);
    }
}