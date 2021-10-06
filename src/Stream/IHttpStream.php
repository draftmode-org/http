<?php


namespace Terrazza\Component\Http\Stream;


use Psr\Http\Message\StreamInterface;

interface IHttpStream extends StreamInterface {
    public function getContents(bool $rewindAfterReading=false): string;
    public function setMediaType(string $mediaType) : self;
    public function getMediaType() :?string;
    public function setFileName(string $fileName) : self;
    public function getFileName() :?string;
}