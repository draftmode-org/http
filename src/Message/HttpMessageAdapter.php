<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Message;

use Terrazza\Component\Http\Request\HttpServerRequest;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Terrazza\Component\Http\Stream\UploadedFile;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Terrazza\Component\Http\Message\Uri\Uri;

class HttpMessageAdapter implements HttpMessageAdapterInterface {
    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     * @noinspection SpellCheckingInspection
     */
    public function getServerRequestFromGlobals(): HttpServerRequestInterface {
        $streamFactory                              = new HttpStreamFactory();
        $method                                     = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers                                    = $this->getAllHeaders();
        $uri                                        = $this->getUriFromGlobals();
        $body                                       = $streamFactory->createStreamFromFile('php://input', 'r+', 4096);
        $protocol                                   = isset($_SERVER['SERVER_PROTOCOL']) ?
            str_replace('HTTP/', '', trim($_SERVER['SERVER_PROTOCOL'])) :
            '1.1';

        $serverRequest                              = new HttpServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

        //
        // pass uri QueryParams to request->queryParams
        //
        $uriQueryParams                            = [];
        parse_str($serverRequest->getUri()->getQuery(), $uriQueryParams);
        if (count($uriQueryParams)) {
            $serverRequest                          = $serverRequest->withQueryParams($uriQueryParams);
        }

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles($this->normalizeFiles($_FILES));
    }

    /**
     * @return array|false
     */
    protected function getAllHeaders() {
        return (function_exists("\getallheaders")) ? getallheaders() : [];
    }

    /**
     * @param HttpResponseInterface $response
     */
    public function emitResponse(HttpResponseInterface $response): void {
        //
        // http header
        //
        $statusLine = sprintf('HTTP/%s %s %s'
            , $response->getProtocolVersion()
            , $response->getStatusCode()
            , $response->getReasonPhrase()
        );
        header($statusLine, TRUE);
        //
        // extend Content-Length to Header, if missing
        //
        $hContentLength                             = "Content-Length";
        if ($response->getBody()->getSize() !== null && $response->getBody()->getSize() > 0 && !$response->hasHeader($hContentLength)) {
            $response->withHeader($hContentLength, (string)$response->getBody()->getSize());
        }
        //
        // set header
        //
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeader = sprintf('%s: %s'
                , $name
                , $response->getHeaderLine($name)
            );
            header($responseHeader, FALSE);
        }
        //
        // only return body with contentSize
        //
        echo $response->getBody()->getContents();
    }

    /**
	 * Return an UploadedFile instance array.
	 *
	 * @param array $files A array which respect $_FILES structure
	 *
	 * @return array
	 * @throws InvalidArgumentException for unrecognized values
	 */
    protected function normalizeFiles(array $files): array {
        $normalized                                 = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key]                   = $value;
            } elseif (is_array($value)) {
                if (isset($value['tmp_name'])) {
                    $normalized[$key]               = $this->createUploadedFileFromSpec($value);
                } else {
                    $normalized[$key]               = $this->normalizeFiles($value);
                }
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }
        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     *
     * @return UploadedFileInterface|array
     */
    protected function createUploadedFileFromSpec(array $value) {
        if (is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }
        return new UploadedFile(
            $value['tmp_name'],
            (int)$value['size'],
            (int)$value['error'],
            $value['name'],
            $value['type']
        );
    }

	/**
	 * Normalize an array of file specifications.
	 *
	 * Loops through all nested files and returns a normalized array of
	 * UploadedFileInterface instances.
	 *
	 * @param array $files
	 * @return array
	 */
    protected function normalizeNestedFileSpec(array $files = []): array {
        $normalizedFiles = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name'                          => $files['tmp_name'][$key],
                'size'                              => $files['size'][$key],
                'error'                             => $files['error'][$key],
                'name'                              => $files['name'][$key],
                'type'                              => $files['type'][$key],
            ];
            $normalizedFiles[$key]                  = $this->createUploadedFileFromSpec($spec);
        }
        return $normalizedFiles;
    }

    protected function extractHostAndPortFromAuthority(string $authority): array {
        $uri                                        = "http://" . $authority;
        $parts                                      = parse_url($uri);
        if ($parts === false) {
            return [null, null];
        }
        $host                                       = $parts['host'] ?? null;
        $port                                       = $parts['port'] ?? null;
        return [$host, $port];
    }

    /**
     * Get a Uri populated with values from $_SERVER.
     */
    public function getUriFromGlobals(): UriInterface {
        $uri                                        = new Uri('');
        $uri                                        = $uri->withScheme(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
        $hasPort                                    = false;
        if (isset($_SERVER['HTTP_HOST'])) {
            [$host, $port]                          = $this->extractHostAndPortFromAuthority($_SERVER['HTTP_HOST']);
            if ($host !== null) {
                $uri                                = $uri->withHost($host);
            }
            if ($port !== null) {
                $hasPort                            = true;
                $uri                                = $uri->withPort($port);
            }
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $uri                                    = $uri->withHost($_SERVER['SERVER_NAME']);
        } elseif (isset($_SERVER['SERVER_ADDR'])) {
            $uri                                    = $uri->withHost($_SERVER['SERVER_ADDR']);
        }
        if (!$hasPort && isset($_SERVER['SERVER_PORT'])) {
            $uri                                    = $uri->withPort($_SERVER['SERVER_PORT']);
        }
        $hasQuery                                   = false;
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUriParts                        = explode('?', $_SERVER['REQUEST_URI'], 2);
			$scriptDir                              = dirname($_SERVER['SCRIPT_NAME']);
			if (strpos($requestUriParts[0], $scriptDir) === 0) {
				$requestUriParts[0]                 = substr($requestUriParts[0], strlen($scriptDir));
			}
            $uri                                    = $uri->withPath($requestUriParts[0]);
            if (isset($requestUriParts[1])) {
                $hasQuery                           = true;
                $uri                                = $uri->withQuery($requestUriParts[1]);
            }
        }
        if (!$hasQuery && isset($_SERVER['QUERY_STRING'])) {
            $uri                                    = $uri->withQuery($_SERVER['QUERY_STRING']);
        }
        return $uri;
    }
}