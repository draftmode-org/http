<?php
namespace Terrazza\Component\Http\Tests\Request;

use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Terrazza\Component\Http\Stream\UploadedFile;

class HttpClientRequestBodyTest extends TestCase {
    function test_body_null() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("", null);
        $this->assertEquals(0, $request->getBody()->getSize());
    }

    function test_get_body_stream() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withBody((new HttpStreamFactory)->createStream("myString"));
        $this->assertEquals(8, $request->getBody()->getSize());
    }

    //
    // application/x-www-form-urlencoded
    //
    function test_application_x_www_form_urlencoded_array() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/x-www-form-urlencoded", ["content"]);
        $this->assertEquals("0=content", $request->getBody()->getContents());
    }

    function test_application_x_www_form_urlencoded_object() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/x-www-form-urlencoded", (object)["content"]);
        $this->assertEquals("0=content", $request->getBody()->getContents());
    }

    //
    // application/json
    //
    function test_application_json_null() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("application/json");
        $this->assertEquals(0, $request->getBody()->getSize());
    }

    function test_application_json_array() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/json", ["content"]);
        $this->assertEquals('["content"]', $request->getBody()->getContents());
    }

    function test_application_json_object() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/json", (object)["content"]);
        $this->assertEquals('{"0":"content"}', $request->getBody()->getContents());
    }

    function test_application_json_string() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/json", "content");
        $this->assertEquals('"content"', $request->getBody()->getContents());
    }

    function test_application_json_int() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("application/json", 12);
        $this->assertEquals('12', $request->getBody()->getContents());
    }

    //
    // multipart/form-data
    //
    function test_application_form_data_array() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("multipart/form-data", ["field" => "value"]);
        $this->assertEquals(91, $request->getBody()->getSize());
    }

    function test_application_form_data_arrays() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContent("multipart/form-data", ["field" => ["value", "value2"]]);
        $this->assertEquals(187, $request->getBody()->getSize());
    }

    function test_application_form_data_file() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("multipart/form-data")
            ->withUploadedFile("fileName", new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR. "UploadFile.txt"));
        $this->assertEquals(139, $request->getBody()->getSize());
    }

    function test_application_form_data_files() {
        $request = (new HttpClientRequest("POST", "https://www.google.at"))
            ->withContentType("multipart/form-data")
            ->withUploadedFiles(["fileName" => [
                new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR. "UploadFile.txt"),
                new UploadedFile(__DIR__ . DIRECTORY_SEPARATOR. "UploadFile.txt")
            ]]);
        $this->assertEquals(282, $request->getBody()->getSize());
    }
}