<?php
namespace Terrazza\Component\Http\Tests\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

class HttpStreamFactoryTest extends TestCase {

    function testFailureCreateFromFile() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpStreamFactory())->createStreamFromFile("unkownFile", "r");
    }

    function testFailureCreateFromResource() {
        $this->expectException(InvalidArgumentException::class);
        (new HttpStreamFactory())->createStreamFromResource("unkownResource");
    }
}