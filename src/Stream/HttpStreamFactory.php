<?php
namespace Terrazza\Component\Http\Stream;
use InvalidArgumentException;
use RuntimeException;

class HttpStreamFactory implements IHttpStreamFactory {
    /**
     * @param string $content
     * @return IHttpStream
     */
    public function createStream(string $content=""): IHttpStream {
        $stream                                     = fopen('php://temp', "r+");
        // @codeCoverageIgnoreStart
        if ($stream === false) {
            throw new RuntimeException("failed to create stream");
        }
        // @codeCoverageIgnoreEnd
        fwrite($stream, $content);
        fseek($stream, 0);
        return new HttpStream($stream);
    }

    /**
     * @param string $filename
     * @param string $mode
     * @param int $bufferSize
     * @return IHttpStream
     */
    public function createStreamFromFile(string $filename, string $mode = 'r', int $bufferSize=0): IHttpStream {
        $resource                                   = @fopen($filename, $mode);
        if ($resource === false) {
            throw new InvalidArgumentException(sprintf('invalid file %s to create stream', $filename));
        }
        $stream                                     = new HttpStream($resource);
        // filename can be touched
        if (file_exists($filename)) {
            // set mediaType + fileName
            $stream
                ->setMediaType(mime_content_type($filename))
                ->setFileName($filename);
        }
        return $stream;
    }

    public function createStreamFromResource($resource): IHttpStream {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('resource must be a resource');
        }
        return new HttpStream($resource);
    }
}