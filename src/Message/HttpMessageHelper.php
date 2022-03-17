<?php

declare(strict_types=1);

namespace Terrazza\Component\Http\Message;

use Terrazza\Component\Http\Stream\HttpStreamInterface;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Trait implementing functionality common to requests and responses.
 */
trait HttpMessageHelper {
	
    /** @var array<string, string[]> Map of all registered headers, as original name => array of values */
    private array $headers = [];

    /** @var array<string, string> Map of lowercase header name => original name at registration */
    private array $headerMapping  = [];

    /** @var string */
    private string $protocolVersion = '1.1';

    /** @var StreamInterface|null */
    private ?StreamInterface $stream = null;

    /** @var mixed|null */
    private $body = null;

    /**
     * @var array|UploadedFileInterface[]
     */
    private array $uploadFiles = [];

    public function getProtocolVersion(): string {
        return $this->protocolVersion;
    }

	/**
	 * @param string $version
	 * @return static
	 */
    public function withProtocolVersion($version): self {
        if ($this->protocolVersion === $version) {
            return $this;
        }
        //
        $message = clone $this;
        $message->protocolVersion = $version;
        return $message;
    }

	/**
	 * @return string[][]
	 */
    public function getHeaders(): array {
        return $this->headers;
    }

    public function hasHeader($header): bool {
        return array_key_exists(strtolower($header), $this->headerMapping);
    }

	/**
	 * @param string $header
	 * @return string[]
	 */
    public function getHeader($header): array {
	    $headerKey                                  = strtolower($header);
        return array_key_exists($headerKey, $this->headerMapping) ?
	        $this->headers[$this->headerMapping[$headerKey]] : [];
    }

    public function getHeaderLine($header): string {
        return implode(', ', $this->getHeader($header));
    }

	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return static
	 */
    public function withHeader($name, $value): self {
        $this->assertHeader($name);
        $value                                      = $this->normalizeHeaderValue($value);
        $headerKey                                  = strtolower($name);
        // overwrite header
        $message                                    = clone $this;
        $message->headers[$name]                    = $value;
        $message->headerMapping[$headerKey]         = $name;
        return $message;
    }

	/**
	 * @param string $name
	 * @param string|string[] $value
	 * @return static
	 */
    public function withAddedHeader($name, $value): self {
        $this->assertHeader($name);
        $value                                      = $this->normalizeHeaderValue($value);
	    $headerKey                                  = strtolower($name);
	    // add header
        $message                                    = clone $this;
        $message->headers[$name]                    = array_merge($this->headers[$name] ?? [], $value);
        $message->headerMapping[$headerKey]         = $name;
        return $message;
    }

	/**
	 * @param string $name
	 * @return static
	 */
    public function withoutHeader($name): self {
	    $headerKey                                  = strtolower($name);
        $name                                       = $this->headerMapping[$headerKey];
        // remove header
        unset($this->headers[$name], $this->headerMapping[$headerKey]);
        return $this;
    }

    public function getBody(): HttpStreamInterface {
        return $this->stream ??= (new HttpStreamFactory)->createStream($this->body ?? "");
    }

    public function withBody(StreamInterface $body): self {
        if ($this->stream === $body) {
            return $this;
        }
        //
        $message                                    = clone $this;
        $message->stream                            = $body;
        return $message;
    }

	/**
	 * @param array $headers
	 */
    private function setHeaders(array $headers): void {
        $this->headerMapping                        = [];
        $this->headers                              = [];
        foreach ($headers as $header => $value) {
            if (is_int($header)) {
                // Numeric array keys are converted to int by PHP but having a header name '123' is not forbidden by the spec
                // and also allowed in withHeader(). So we need to cast it to string again for the following assertion to pass.
                $header                             = (string) $header;
            }
            $this->assertHeader($header);
            $value                                  = $this->normalizeHeaderValue($value);
            $headerKey                              = strtolower($header);

	        $header                                 = $this->headerMapping[$headerKey] ??= $header;
	   	    $this->headers[$header]                 = array_merge($this->headers[$header] ?? [], $value);
        }
    }

    /**
     * @param mixed $value
     *
     * @return string[]
     */
    private function normalizeHeaderValue($value): array {
        if (!is_array($value)) {
            return $this->trimHeaderValues([$value]);
        }
        if (count($value) === 0) {
            throw new InvalidArgumentException('header value can not be an empty array.');
        }
        return $this->trimHeaderValues($value);
    }

    /**
     * Trims whitespace from the header values.
     *
     * Spaces and tabs ought to be excluded by parsers when extracting the field value from a header field.
     *
     * header-field = field-name ":" OWS field-value OWS
     * OWS          = *( SP / HTAB )
     *
     * @param mixed[] $values Header values
     *
     * @return string[] Trimmed header values
     *
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     */
    private function trimHeaderValues(array $values): array {
        return array_map(static function ($value) {
            if (!is_string($value) && $value !== null) {
                throw new InvalidArgumentException(sprintf(
                    'header value must be scalar or null but %s provided.',
                    is_object($value) ? get_class($value) : gettype($value)
                ));
            }
            return trim((string) $value, " \t");
        }, array_values($values));
    }

    /**
     * @see https://tools.ietf.org/html/rfc7230#section-3.2
     *
     * @param mixed $header
     */
    private function assertHeader($header): void {
        if (!is_string($header)) {
            throw new InvalidArgumentException(sprintf(
                'Header name must be a string but %s provided.',
                is_object($header) ? get_class($header) : gettype($header)
            ));
        }
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $header)) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not valid header name', $header)
            );
        }
    }
}
