<?php
namespace Terrazza\Component\Http\Tests\Request;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\Exception\HttpRequestInvalidUploadFileException;
use Terrazza\Component\Http\Request\Exception\HttpRequestUnexpectedBodyException;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Stream\UploadedFile;

class HttpClientRequestBodyExceptionTest extends TestCase {

    function testBodyArray() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("", ["data"]);
        $this->expectException(HttpRequestUnexpectedBodyException::class);
        $request->getBody();
    }

    //
    // multipart/form-data
    //
    function testContentType_multipart_form_data_noContent() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("multipart/form-data");
        $this->expectException(HttpRequestUnexpectedBodyException::class);
        $request->getBody();
    }

    function testContentType_multipart_form_data_content_string() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("multipart/form-data", "myString");
        $this->expectException(HttpRequestUnexpectedBodyException::class);
        $request->getBody();
    }

    function test_application_form_data_files_uploadFileException() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("multipart/form-data")
            ->withUploadedFiles(["fileName" => [new UploadedFile(__FILE__), "fine"]]);
        $this->expectException(HttpRequestInvalidUploadFileException::class);
        $request->getBody()->getSize();
    }

    function test_application_form_data_file_uploadFileException() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("multipart/form-data")
            ->withUploadedFiles(["fileName" => "fine"]);
        $this->expectException(HttpRequestInvalidUploadFileException::class);
        $request->getBody()->getSize();
    }

    //
    // application/x-www-form-urlencoded
    //
    function testContentType_application_x_www_form_urlencoded_noContent() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("application/x-www-form-urlencoded");
        $this->expectException(HttpRequestUnexpectedBodyException::class);
        $request->getBody();
    }

    function testContentType_application_x_www_form_urlencoded_string() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/x-www-form-urlencoded", "string");
        $this->expectException(HttpRequestUnexpectedBodyException::class);
        $request->getBody();
    }
}