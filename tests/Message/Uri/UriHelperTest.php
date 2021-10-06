<?php
namespace Terrazza\Component\Http\Tests\Message\Uri;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Message\Uri\UriHelper;

class UriHelperTest extends TestCase {

    function testAbsolute() {
        $this->assertEquals([
            true,
            false
        ],[
            UriHelper::isAbsolute(new Uri("https://www.google.com")),
            UriHelper::isAbsolute(new Uri("www.google.com"))
        ]);
    }

    function testDefaultPort() {
        $this->assertEquals([
            true,
            false
        ],[
            UriHelper::isDefaultPort(new Uri("https://www.google.com")),
            UriHelper::isDefaultPort(new Uri("www.google.com:9876"))
        ]);
    }

    function testAbsolutePath() {
        $this->assertEquals([
            true,
            false,
        ],[
            UriHelper::isAbsolutePathReference(new Uri("/etc/test.jpg")),
            UriHelper::isAbsolutePathReference(new Uri("https://www.google.com")),
        ]);
    }

    function testRelativePath() {
        $this->assertEquals([
            true,
            false,
        ],[
            UriHelper::isRelativePathReference(new Uri("etc/test.jpg")),
            UriHelper::isRelativePathReference(new Uri("https://www.google.com")),
        ]);
    }

    function testNetworkPathReference() {
        $this->assertEquals([
            true,
            false
        ],[
            UriHelper::isNetworkPathReference((new Uri("https://user:password@www.google.com"))->withScheme("")),
            UriHelper::isNetworkPathReference(new Uri("https://www.google.com")),
        ]);
    }
}