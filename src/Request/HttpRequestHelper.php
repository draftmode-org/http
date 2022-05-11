<?php
namespace Terrazza\Component\Http\Request;

use Terrazza\Component\Http\Message\Uri\Uri;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

trait HttpRequestHelper {

    /** @var string */
    private string $method;

    /** @var UriInterface */
    private UriInterface $uri;

    /**
     * @var array
     */
    private array $queryParams = [];

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @var array
     */
    private array $uploadedFiles = [];

    /**
     * @var array|null
     */
    private ?array $pathArguments = null;

    /**
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array $headers HttpClientRequest headers
     * @param string|resource|StreamInterface|null|array $body HttpClientRequest body
     * @param string $version ProtocolVersion version
     */
    private function initializeRequest(
        string $method,
        $uri,
        array $headers                              = [],
        $body                                       = null,
        string $version                             = '1.1'
    ): void {
        $this->assertMethod($method);
        if (!($uri instanceof UriInterface)) {
            $uri                                    = new Uri($uri);
        }
        $this->method                               = strtoupper($method);
        $this->uri                                  = $uri;
        $this->setHeaders($headers);
        $this->protocolVersion = $version;

        if (!array_key_exists('host', $this->headerMapping)) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->body     = $body;
        }
    }

    /**
     * @return string
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @param string $method
     * @return static
     */
    public function withMethod($method): self {
        $this->assertMethod($method);
        $this->method                               = $method;
        return $this;
    }

    /**
     * @param mixed $method
     */
    private function assertMethod($method): void {
        if (!is_string($method) || $method === '') {
            throw new InvalidArgumentException('Method must be a non-empty string.');
        }
    }

    public function __clone() {
        $this->pathArguments = null;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self {
        if ($uri === $this->uri) {
            return $this;
        }
        //
        $request                                    = clone $this;
        $request->uri                               = $uri;
        if (!$preserveHost || !isset($this->headerMapping['host'])) {
            $request->updateHostFromUri();
        }
        return $request;
    }

    /**
     * @return string
     */
    public function getRequestTarget() : string {
        return $this->requestTarget ?? $this->buildRequestTarget();
    }

    /**
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget): self {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('invalid request target provided; cannot contain whitespace');
        }
        $request                                    = clone $this;
        $request->requestTarget                     = $requestTarget;
        return $request;
    }

    /**
     * @return string
     */
    private function buildRequestTarget(): string {
        if (!isset($this->uri)) {
            throw new InvalidArgumentException('uri is not set, cannot build requestTarget');
        }
        $target                                     = $this->uri->getPath();
        if ($target === '') {
            $target                                 = '/';
        }
        $queryParams                                = $this->getQueryParams();
        if (count($queryParams)) {
            $target                                 .= '?' . http_build_query($queryParams);
        }
        return $target;
    }

    public function getQueryParams(): array {
        return $this->queryParams;
    }

    /**
     * @param array $queryParams
     * @return static
     */
    public function withQueryParams(array $queryParams): self {
        if (count($queryParams) === 0) {
            return $this;
        }
        $request                                    = clone $this;
        $request->queryParams                       = $queryParams;
        if (isset($request->uri)) {
            $request->uri                           = $request->getUri()->withQuery($this->buildQueryParam($queryParams));
        }
        return $request;
    }

    private function buildQueryParam(array $query) : string {
        return http_build_query($query);
    }

    private function updateHostFromUri(): void {
        $host = $this->uri->getHost();
        if ($host === '') {
            return;
        }
        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }
        if (isset($this->headerMapping['host'])) {
            $header = $this->headerMapping['host'];
        } else {
            $header = 'Host';
            $this->headerMapping['host'] = 'Host';
        }
        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$header => [$host]] + $this->headers;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null) {
        if (array_key_exists($name, $this->attributes) === false) {
            return $default;
        }
        return $this->attributes[$name];
    }

    /**
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): self {
        $request                                    = clone $this;
        $request->attributes[$name]                 = $value;
        return $request;
    }

    /**
     * @param string $name
     * @return static
     */
    public function withoutAttribute($name): self {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }
        $request                                    = clone $this;
        unset($request->attributes[$name]);
        return $request;
    }


    /**
     * custom extensions - start
     */

    public function getPathParams(string $routeUri) : array {
        if (is_null($this->pathArguments)) {
            $argumentRegx                           = '#\{([\w\_]+)\}#';
            $pathArgs                               = [];
            $uriArgs                                = [];
            $routeUri                               = "/". trim($routeUri, "/");
            if (preg_match_all($argumentRegx, $routeUri, $matches)) {
                $pathArgs                           = $matches[1] ?? [];
            }
            $requestUri                             = $this->getUri()->getPath();
            $routeUri                               = '^' . preg_replace($argumentRegx, '(.+?)', $routeUri) . '$';
            if (preg_match("#".$routeUri."#", "/" . trim($requestUri, "/"), $matches)) {
                $uriArgs                            = array_slice($matches, 1);
            }
            if (count($pathArgs) && count($uriArgs)) {
                $this->pathArguments                = array_combine($pathArgs, $uriArgs);
            } else {
                $this->pathArguments                = [];
            }
        }
        return $this->pathArguments;
    }

    /**
     * @param string $routeUri
     * @param string $argumentName
     * @return string|null
     */
    public function getPathParam(string $routeUri, string $argumentName) :?string {
        $pathArguments                              = $this->getPathParams($routeUri);
        if (array_key_exists($argumentName, $pathArguments)) {
            return $pathArguments[$argumentName];
        } else {
            return null;
        }
    }

    /**
     * @param string $argumentName
     * @return string|null
     */
    public function getQueryParam(string $argumentName) :?string {
        $params                                     = $this->getQueryParams();
        if (array_key_exists($argumentName, $params)) {
            return $params[$argumentName];
        }
        return null;
    }

    public function withUploadedFiles(array $uploadFiles) : self {
        foreach ($uploadFiles ?? [] as $formDataName => $uploadFile) {
            if (is_array($uploadFile)) {
                $uploadFiles                        = [];
                foreach ($uploadFile as $uploadSingleFile) {
                    $uploadFiles[]                  = $uploadSingleFile;
                }
                $this->uploadFiles[$formDataName]   = $uploadFiles;
            } else {
                $this->uploadFiles[$formDataName]   = $uploadFile;
            }
        }
        return $this;
    }

    public function withUploadedFile(string $formDataName, UploadedFileInterface $uploadedFile) : self {
        $this->uploadFiles[$formDataName]           = $uploadedFile;
        return $this;
    }
}