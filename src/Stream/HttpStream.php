<?php
namespace Terrazza\Component\Http\Stream;
use InvalidArgumentException;
use RuntimeException;

class HttpStream implements HttpStreamInterface {
    /**
     * @see http://php.net/manual/function.fopen.php
     * @see http://php.net/manual/en/function.gzopen.php
     */
    private const READABLE_MODES = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    private const WRITABLE_MODES = '/a|w|r\+|rb\+|rw|x|c/';

    /** @var resource|null */
    private $stream;
    /** @var int|null */
    private ?int $size = null;
    /** @var bool */
    private bool $seekable;
    /** @var bool */
    private bool $readable;
    /** @var bool */
    private bool $writable;
    /** @var string|null */
    private ?string $uri;
    /** @var mixed[] */
    private $customMetadata;
    /** @var string|null  */
    private ?string $mediaType=null;
    /** @var string|null  */
    private ?string $fileName=null;

    /**
     * This constructor accepts an associative array of options.
     *
     * - size: (int) If a read stream would otherwise have an indeterminate
     *   size, but the size is known due to foreknowledge, then you can
     *   provide that size, in bytes.
     * - metadata: (array) Any additional metadata to return when the metadata
     *   of the stream is accessed.
     *
     * @param resource                            $stream  HttpStream resource to wrap.
     * @param array{size?: int, metadata?: array} $options Associative array of options.
     *
     * @throws InvalidArgumentException if the stream is not a stream resource
     */
    public function __construct($stream, array $options = []) {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('stream must be a resource');
        }

        if (isset($options['size'])) {
            $this->size = $options['size'];
        }

        $this->customMetadata                       = $options['metadata'] ?? [];
        $this->stream                               = $stream;
        $meta                                       = stream_get_meta_data($this->stream);
        $this->seekable                             = $meta['seekable'];
        $this->readable                             = (bool)preg_match(self::READABLE_MODES, $meta['mode']);
        $this->writable                             = (bool)preg_match(self::WRITABLE_MODES, $meta['mode']);
        $uri                                        = $this->getMetadata('uri');
        $this->uri                                  = $uri !== null && !is_array($uri) ? (string)$uri : null;
    }

    public function __destruct() {
        $this->close();
    }

    public function __toString() {
        if ($this->isSeekable()) {
            $this->seek(0);
        }
        return $this->getContents();
    }

    public function close() {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    public function detach() {
        if (!isset($this->stream)) {
            return null;
        }
        $this->stream                               = null;
        $this->size                                 = null;
        $this->uri                                  = null;
        $this->readable                             = false;
        $this->writable                             = false;
        $this->seekable                             = false;
    }

    public function getSize() :?int {
        if ($this->size !== null) {
            return $this->size;
        }
        if (!isset($this->stream)) {
            return null;
        }
        // Clear the stat cache if the stream has a URI
        // @codeCoverageIgnoreStart
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }
        // @codeCoverageIgnoreEnd
        $stats                                      = fstat($this->stream);
        if (is_array($stats) && isset($stats['size'])) {
            $this->size                             = $stats['size'];
            return $this->size;
        }
        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    public function setMediaType(?string $mediaType) : self {
        $this->mediaType = $mediaType;
        return $this;
    }
    public function getMediaType() :?string {
        return $this->mediaType;
    }

    public function setFileName(?string $fileName) : self {
        $this->fileName = $fileName;
        return $this;
    }
    public function getFileName() :?string {
        return $this->fileName;
    }

    private function checkStream() : void {
        if (!isset($this->stream)) {
            throw new RuntimeException('stream is detached');
        }
    }
    private function checkSeekable() : void {
        // @codeCoverageIgnoreStart
        if (!$this->seekable) {
            throw new RuntimeException('stream is not seekable');
        }
        // @codeCoverageIgnoreEnd
    }
    private function checkWriteable() : void {
        if (!$this->writable) {
            throw new RuntimeException('cannot write to a non-writable stream');
        }
    }
    private function checkReadable() : void {
        if (!$this->readable) {
            throw new RuntimeException('cannot read from non-readable stream');
        }
    }

    public function tell() : int {
        $this->checkStream();
        $result = ftell($this->stream);
        // @codeCoverageIgnoreStart
        if ($result === false) {
            throw new RuntimeException('unable to determine stream position');
        }
        // @codeCoverageIgnoreEnd
        return $result;
    }

    public function eof() : bool {
        $this->checkStream();
        return feof($this->stream);
    }

    public function isSeekable() : bool {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET) : void {
        $whence = (int) $whence;
        $this->checkStream();
        $this->checkSeekable();
        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('unable to seek to stream position '
                . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind() : void {
        $this->checkSeekable();
        $this->seek(0);
    }

    public function isWritable() : bool {
        return $this->writable;
    }

    public function write($string) : int {
        $this->checkStream();
        $this->checkWriteable();
        // we can't know the size after writing anything
        $this->size                                 = null;
        $result                                     = fwrite($this->stream, $string);
        // @codeCoverageIgnoreStart
        if ($result === false) {
            throw new RuntimeException('unable to write to stream');
        }
        // @codeCoverageIgnoreEnd
        return $result;
    }

    public function isReadable() : bool {
        return $this->readable;
    }

    public function read($length) : string {
        $this->checkStream();
        $this->checkReadable();
        if ($length < 0) {
            throw new RuntimeException('Length parameter cannot be negative');
        }
        if ($length === 0) {
            return '';
        }
        $string                                     = fread($this->stream, $length);
        // @codeCoverageIgnoreStart
        if ($string === false) {
            throw new RuntimeException('Unable to read from stream');
        }
        // @codeCoverageIgnoreEnd
        return $string;
    }

    public function getContents(bool $rewindAfterReading=false): string {
        $this->checkStream();
        $contents                                   = stream_get_contents($this->stream);
        // @codeCoverageIgnoreStart
        if ($contents === false) {
            throw new RuntimeException('unable to read stream contents');
        }
        // @codeCoverageIgnoreEnd
        if (!$rewindAfterReading) {
            $this->rewind();
        }
        return $contents;
    }

    public function getMetadata($key = null) {
        if ($key) {
            return $this->customMetadata[$key] ??
                ($this->stream ? stream_get_meta_data($this->stream) : null) ?? null;
        }
        return $this->stream ?
            $this->customMetadata + stream_get_meta_data($this->stream) : [];
    }
}