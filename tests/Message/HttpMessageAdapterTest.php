<?php
namespace Terrazza\Component\Http\Tests\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Terrazza\Component\Http\Message\HttpMessageAdapter;
use Terrazza\Component\Http\Response\HttpResponse;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Terrazza\Component\Http\Stream\UploadedFile;

class HttpMessageAdapterTest extends TestCase {

    protected function setUpServer(): void {
        unset($_SERVER["SERVER_PROTOCOL"]);
        unset($_SERVER["HTTP_HOST"]);
        unset($_SERVER["SERVER_NAME"]);
        unset($_SERVER["SERVER_PORT"]);
        unset($_SERVER["SERVER_ADDR"]);
        unset($_SERVER["REQUEST_URI"]);
        unset($_SERVER["QUERY_STRING"]);
    }

    function testGetServerRequestFromGlobalsWithServerAddr() {
        $this->setUpServer();
        $_SERVER["SERVER_PROTOCOL"]                 = "HTTP/1.1";
        $host                                       = "www.example.com";
        $port                                       = null;
        $_SERVER["SERVER_ADDR"]                     = "$host";
        $request = (new HttpMessageAdapterTestClass())->getServerRequestFromGlobals(new HttpStreamFactory());
        $this->assertEquals([
            $host,
            $port
        ], [
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
        ]);
    }

    function testGetServerRequestFromGlobalsWithHttpHostAndQueryString() {
        $this->setUpServer();
        $_SERVER["SERVER_PROTOCOL"]                 = "HTTP/1.1";
        $host                                       = "www.example.com";
        $port                                       = 123;
        $queryString                                = "tag=networking&order=newest";
        $_SERVER["HTTP_HOST"]                       = "$host:$port?$queryString#top";
        $_SERVER["QUERY_STRING"]                    = $queryString;
        $request = (new HttpMessageAdapterTestClass())->getServerRequestFromGlobals(new HttpStreamFactory());
        $this->assertEquals([
            $host,
            $port,
            ["tag" => "networking", "order" => "newest"],
            $queryString
        ], [
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $request->getQueryParams(),
            $request->getUri()->getQuery()
        ]);
    }

    function testGetServerRequestFromGlobalsWithRequestUriLocal() {
        $this->setUpServer();
        $_SERVER["SCRIPT_NAME"]                     = __FILE__;
        $path                                       = "/forum/questions";
        $_SERVER["REQUEST_URI"]                     = dirname(__FILE__).$path;
        $request = (new HttpMessageAdapterTestClass())->getServerRequestFromGlobals(new HttpStreamFactory());
        $this->assertEquals([
            $path,
        ],[
            $request->getUri()->getPath()
        ]);
    }

    function testGetServerRequestFromGlobalsWithServerNameAndRequestUri() {
        $this->setUpServer();
        $_SERVER["SERVER_PROTOCOL"]                 = "HTTP/1.1";
        $host                                       = "www.example.com";
        $port                                       = 123;
        $_SERVER["SERVER_NAME"]                     = "$host";
        $_SERVER["SERVER_PORT"]                     = $port;
        $queryString                                = "tag=networking&order=newest";
        $_SERVER["REQUEST_URI"]                     = "/forum/questions?$queryString";
        $request = (new HttpMessageAdapterTestClass())->getServerRequestFromGlobals(new HttpStreamFactory());
        $this->assertEquals([
            $host,
            $port,
            ["tag" => "networking", "order" => "newest"],
            $queryString,
        ], [
            $request->getUri()->getHost(),
            $request->getUri()->getPort(),
            $request->getQueryParams(),
            $request->getUri()->getQuery()
        ]);
    }

    function testNormalizeFilesInvalid() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpMessageAdapterTestClass())->normalizeFiles(["yes"]);
    }

    function testNormalizeFileTmpSingle() {
        $files = (new HttpMessageAdapterTestClass())->normalizeFiles([
            "mediaFile" => [
                "tmp_name"                          => __FILE__,
                "size"                              => filesize(__FILE__),
                "error"                             => 0,
                "name"                              => basename(__FILE__),
                "type"                              => "",
            ]
        ]);
        $this->assertInstanceOf(UploadedFileInterface::class, $files["mediaFile"]);
    }

    function testNormalizeFileTmpMultiple() {
        $fileName                                   = "file1";
        $files = (new HttpMessageAdapterTestClass())->normalizeFiles([
            "mediaFiles" => [
                "tmp_name"                          => [$fileName => __FILE__],
                "size"                              => [$fileName => filesize(__FILE__)],
                "error"                             => [$fileName => 0],
                "name"                              => [$fileName => basename(__FILE__)],
                "type"                              => [$fileName => ""],
            ]
        ]);
        $this->assertInstanceOf(UploadedFileInterface::class, $files["mediaFiles"][$fileName]);
    }

    function testNormalizeFileUploadFile() {
        $files = (new HttpMessageAdapterTestClass())->normalizeFiles([
            "mediaFile" => new UploadedFile(
                __FILE__,
                filesize(__FILE__),
                0,
                basename(__FILE__)
            )
        ]);
        $this->assertInstanceOf(UploadedFileInterface::class, $files["mediaFile"]);
    }

    function testExtractHostAndPortFromAuthorityNull() {
        $extractedHost = (new HttpMessageAdapterTestClass())->extractHostAndPortFromAuthority("");
        $this->assertEquals([null, null], $extractedHost);
    }

    function testExtractHostAndPortFromAuthorityWithoutPort() {
        $uri = "www.example.com";
        $port = "";
        $extractedHost = (new HttpMessageAdapterTestClass())->extractHostAndPortFromAuthority("john.doe@$uri:$port/forum/questions/?tag=networking&order=newest#top");
        $this->assertEquals([$uri, null], $extractedHost);
    }

    function testExtractHostAndPortFromAuthorityWithPort() {
        $uri = "www.example.com";
        $port = 123;
        $extractedHost = (new HttpMessageAdapterTestClass())->extractHostAndPortFromAuthority("john.doe@$uri:$port/forum/questions/?tag=networking&order=newest#top");
        $this->assertEquals([$uri, $port], $extractedHost);
    }

    /**
     * @runInSeparateProcess
     */
    function testEmitResponseWithBody() {
        $response = new HttpResponse(200, ["CUSTOM" => "12"], "message");
        (new HttpMessageAdapterTestClass())->emitResponse($response);
        $this->assertTrue(true);
    }
}

class HttpMessageAdapterTestClass extends HttpMessageAdapter {
    /**
     * @return array
     */
    public function getAllHeaders() : array {
        return [];
    }
    public function normalizeFiles(array $files) : array {
        return parent::normalizeFiles($files);
    }
    public function extractHostAndPortFromAuthority(string $authority): array {
        return parent::extractHostAndPortFromAuthority($authority);
    }
}