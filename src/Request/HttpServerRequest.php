<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Request;

use InvalidArgumentException;
use Terrazza\Component\Http\Message\HttpMessageHelper;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Terrazza\Component\Http\Stream\UploadedFile;

/**
 * PSR-7 request implementation.
 */
class HttpServerRequest implements HttpServerRequestInterface {
    use HttpMessageHelper;
    use HttpRequestHelper;

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @var array
     */
    private array $cookieParams = [];

    /**
     * @var array|object|null
     */
    private $parsedBody;

    /**
     * @var array
     */
    private array $queryParams = [];

    /**
     * @var array
     */
    private array $serverParams;

    /**
     * @var UploadedFile[]
     */
    private array $uploadedFiles = [];

	/**
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array $headers HttpClientRequest headers
	 * @param string|resource|StreamInterface|null $body HttpClientRequest body
	 * @param string $version ProtocolVersion version
	 */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
	    $this->initializeRequest($method, $uri, $headers, $body, $version);
        $this->serverParams = $serverParams;
    }

    /**
     * @return array
     */
    public function getServerParams(): array {
        return $this->serverParams;
    }

    /**
     * @param string $paramKey
     * @return string|null
     */
    public function getServerParam(string $paramKey) :?string {
        return array_key_exists($paramKey, $this->serverParams) ? $this->serverParams[$paramKey] : null;
    }

    /**
     * @return UploadedFile[]
     */
    public function getUploadedFiles(): array {
        return $this->uploadedFiles;
    }

    /**
     * @return array
     */
    public function getCookieParams(): array {
        return $this->cookieParams;
    }

    /**
     * @param string $paramKey
     * @return string|null
     */
    public function getCookieParam(string $paramKey) :?string {
        return array_key_exists($paramKey, $this->cookieParams) ? $this->cookieParams[$paramKey] : null;
    }

    /**
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies): self {
        $request                                    = clone $this;
        $request->cookieParams                      = $cookies;
        return $request;
    }

    /**
     * @return array|object|null
     */
    public function getParsedBody() {
        return $this->parsedBody;
    }

    public function isValidBody() : void {
        $contentType                                = $this->getHeaderLine("Content-Type");
        if ($this->body) {
            $invalidContentMessage                  = "body is malformed/invalid, expected type $contentType";
            if (preg_match("#application/json#", $contentType)) {
                json_decode($this->body);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException($invalidContentMessage, 400);
                }
            }
        }
    }

    /**
     * @param array|object|null $data
     * @return static
     */
    public function withParsedBody($data): self {
        if (!(is_null($data) || is_array($data) || is_object($data))) {
            throw new InvalidArgumentException("invalid parsed body type. expected (null, array, object), given ".gettype($data));
        }
        $request                                    = clone $this;
        $request->parsedBody                        = $data;
        return $request;
    }
}
