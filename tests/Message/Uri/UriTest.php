<?php
namespace Terrazza\Component\Http\Tests\Message\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Terrazza\Component\Http\Message\Uri\Uri;

class UriTest extends TestCase {

    CONST scheme        = "https";
    CONST port          = 9091;
    CONST host          = "www.google.com";
    CONST path          = "/path";
    CONST user          = "user";
    CONST password      = "password";
    CONST fragment      = "main";
    CONST query         = "query=1";

    private function getUri() : UriInterface {
        $scheme     = self::scheme;
        $port       = self::port;
        $host       = self::host;
        $path       = self::path;
        $user       = self::user;
        $password   = self::password;
        $fragment   = self::fragment;
        $query      = self::query;
        return new Uri("$scheme://$user:$password@$host:$port$path?$query#$fragment");
    }

    function testWithQuery() {
        $uri        = new Uri(self::scheme."://".self::host."?".self::query);
        $nUri       = $uri->withQuery($newQuery = "myPath");
        $this->assertEquals([self::query, $newQuery], [$uri->getQuery(), $nUri->getQuery()]);
    }

    function testUri() {
        $uri = $this->getUri();
        $this->assertEquals([
            self::scheme,
            self::host,
            self::path,
            self::port,
            self::user.":".self::password,
            self::user.":".self::password."@".self::host.":".self::port,
            self::fragment,
            self::query,
            self::scheme."://".self::user.":".self::password."@".self::host.":".self::port.self::path."?query=1#".self::fragment
        ],[
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath(),
            $uri->getPort(),
            $uri->getUserInfo(),
            $uri->getAuthority(),
            $uri->getFragment(),
            $uri->getQuery(),
            (string)$uri
        ]);
    }

    function testModifyIdentically() {
        $uri        = $this->getUri();
        $this->assertEquals([
            $uri,
            $uri,
            $uri,
            $uri,
            $uri,
            $uri,
            $uri,
        ],[
            $uri->withScheme(self::scheme),
            $uri->withHost(self::host),
            $uri->withPort(self::port),
            $uri->withPath(self::path),
            $uri->withQuery(self::query),
            $uri->withFragment(self::fragment),
            $uri->withUserInfo(self::user, self::password)
        ]);
    }

    function testModify() {
        $uri        = $this->getUri();
        $this->assertEquals([
            "new:".self::password,
            $newHost = "www.newhost.io",
            $newPort = 99,
            null,
            $newPath = "/newpath",
            $newFragment = "newpath",
        ],[
            $uri->withUserInfo("new", self::password)->getUserInfo(),
            $uri->withHost($newHost)->getHost(),
            $uri->withPort($newPort)->getPort(),
            $uri->withPort(null)->getPort(),
            $uri->withPath($newPath)->getPath(),
            $uri->withFragment($newFragment)->getFragment(),
        ]);
    }

    function testConstructFailure() {
        $this->expectException(InvalidArgumentException::class);
        new Uri("javascript://");
    }

    function testFilterFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withScheme(1);
    }

    function testUserInfoFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withUserInfo(1);
    }

    function testHostFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withHost(1);
    }

    function testPortFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withPort(1999999);
    }

    function testPathFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withPath(1);
    }

    function testPathFailureSlash() {
        $uri        = new Uri();
        $this->expectException(InvalidArgumentException::class);
        $uri->withPath("//test");
    }

    function testPathFailureNoSlash() {
        $uri        = new Uri("https://www.example.com");
        $this->expectException(InvalidArgumentException::class);
        $uri->withPath("test");
    }

    function testFragmentFailure() {
        $this->expectException(InvalidArgumentException::class);
        $this->getUri()->withFragment(1);
    }
}