<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Stream;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface {
    private const ERROR_CODES = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];
    public const COPY_STREAM_BUFFER_SIZE = 8192;

    /**
     * @var string|null
     */
	private ?string $clientFilename;

    /**
     * @var string|null
     */
	private ?string $clientMediaType;

    /**
     * @var int
     */
	private int $error;

    /**
     * @var string|null
     */
	private ?string $file = null;

    /**
     * @var bool
     */
	private bool $moved = false;

    /**
     * @var int|null
     */
	private ?int $size;

    /**
     * @var StreamInterface|null
     */
	private ?StreamInterface $stream = null;

	/**
	 * @param StreamInterface|string|resource $streamOrFile
	 * @param int|null $size
	 * @param int|null $errorStatus
	 * @param string|null $clientFilename
	 * @param string|null $clientMediaType
	 */
    public function __construct(
        $streamOrFile,
        int $size = null,
        int $errorStatus = null,
        string $clientFilename = null,
        string $clientMediaType = null
    ) {
        $this->setError($errorStatus ?? UPLOAD_ERR_OK);
        $this->size                                 = $size;
        $this->clientFilename                       = $clientFilename;
        $this->clientMediaType                      = $clientMediaType;

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * Depending on the value set file or stream variable
     *
     * @param StreamInterface|string|resource $streamOrFile
     *
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile($streamOrFile): void {
        if (is_string($streamOrFile)) {
            if (!file_exists($streamOrFile)) {
                throw new InvalidArgumentException('invalid file provided for UploadedFile');
            }
            $this->file                             = $streamOrFile;
            $this->size                             = filesize($streamOrFile);
            if (!$this->getClientFilename()) {
                $this->clientFilename               = $streamOrFile;
            }
            if (!$this->getClientMediaType()) {
                $this->clientMediaType              = mime_content_type($streamOrFile);
            }
        } elseif (is_resource($streamOrFile)) {
            $this->stream                           = (new HttpStreamFactory)->createStreamFromResource($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream                           = $streamOrFile;
            if ($streamOrFile instanceof HttpStreamInterface) {
                if (!$this->getClientFilename()) {
                    $this->clientFilename           = $streamOrFile->getFileName();
                }
                if (!$this->getClientMediaType()) {
                    $this->clientMediaType          = $streamOrFile->getMediaType();
                }
                $this->size                         = $streamOrFile->getSize();
            }
        } else {
            throw new InvalidArgumentException('invalid stream or file provided for UploadedFile');
        }
    }

	/**
	 * @param int $error
	 */
    private function setError(int $error): void {
        if (!in_array($error, self::ERROR_CODES, true)) {
            throw new InvalidArgumentException('Invalid error status for UploadedFile');
        }
        $this->error = $error;
    }

	/**
	 * @param mixed $param
	 * @return bool
	 */
    private function isStringNotEmpty($param): bool {
        return is_string($param) && !empty($param);
    }

    /**
     * Return true if there is no upload error
     */
    private function isOk(): bool {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function isMoved(): bool {
        return $this->moved;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive(): void {
        if ($this->isOk() === false) {
            throw new RuntimeException('cannot retrieve stream due to upload error');
        }

        if ($this->isMoved()) {
            throw new RuntimeException('cannot retrieve stream after it has already been moved');
        }
    }

    public function getStream(): StreamInterface {
        $this->validateActive();
        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }
        /** @var string $file */
        $file                                       = $this->file;
        return (new HttpStreamFactory)->createStreamFromFile($file, 'r+');
    }

    public function moveTo($targetPath): void {
        $this->validateActive();
        if ($this->isStringNotEmpty($targetPath) === false) {
            throw new InvalidArgumentException('invalid path provided for move operation; must be a non-empty string');
        }
        if ($this->file) {
            $this->moved = PHP_SAPI === 'cli'
                ? rename($this->file, $targetPath)
                : move_uploaded_file($this->file, $targetPath);
        } else {
        	$this->copyStream($this->getStream(), (new HttpStreamFactory)->createStreamFromFile($targetPath, 'w'));
            $this->moved                            = true;
        }
        if ($this->moved === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('uploaded file could not be moved to %s', $targetPath)
            );
            // @codeCoverageIgnoreEnd
        }
    }

    public function getSize(): ?int {
        return $this->size;
    }

    public function getError(): int {
        return $this->error;
    }

    public function getClientFilename(): ?string {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string {
        return $this->clientMediaType;
    }

	private function copyStream(
	    StreamInterface $source,
	    StreamInterface $dest
	): void {
	    $bufferSize                                 = self::COPY_STREAM_BUFFER_SIZE;
        while (!$source->eof()) {
            if (!$dest->write($source->read($bufferSize))) {
                break;
            }
        }
	}
}
