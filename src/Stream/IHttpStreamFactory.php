<?php
namespace Terrazza\Component\Http\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

interface IHttpStreamFactory extends StreamFactoryInterface {
    public function createStreamFromFile(string $filename, string $mode = 'r', int $bufferSize = 0): StreamInterface;
}