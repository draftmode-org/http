<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Client;

use Terrazza\Component\Http\Client\Exception\HttpClientInvalidHeaderException;
use Terrazza\Component\Http\Client\Exception\HttpClientNetworkException;
use Terrazza\Component\Http\Client\Exception\HttpClientRequestException;
use InvalidArgumentException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Terrazza\Component\Http\Client\Exception\HttpClientUnsupportedProtocolVersionException;
use Terrazza\Component\Http\Request\HttpRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;
use Terrazza\Component\Http\Response\HttpResponseFactoryInterface;
use UnexpectedValueException;

class HttpClient implements HttpClientInterface {
	/**
	 * cURL options.
	 *
	 * @var array
	 */
	private array $curlOptions;

	/**
	 * PSR-17 response factory.
	 *
	 * @var HttpResponseFactoryInterface
	 */
	private HttpResponseFactoryInterface $responseFactory;

	/**
	 * PSR-17 stream factory.
	 *
	 * @var StreamFactoryInterface
	 */
	private StreamFactoryInterface $streamFactory;

	/**
	 * cURL synchronous requests handle.
	 *
	 * @var resource|null
	 */
	private $handle;

	/**
	 * Create HTTP client.
	 *
	 * @param HttpResponseFactoryInterface $responseFactory PSR-17 HTTP response factory.
	 * @param StreamFactoryInterface   $streamFactory   PSR-17 HTTP stream factory.
	 * @param array                         $options         cURL options
	 *                                                       {@link http://php.net/curl_setopt}.
	 *
	 * @since 2.0 Accepts PSR-17 factories instead of HTTPlug ones.
	 */
	public function __construct(
        HttpResponseFactoryInterface $responseFactory,
        StreamFactoryInterface       $streamFactory,
        array                        $options = []
	) {
		$this->responseFactory = $responseFactory;
		$this->streamFactory = $streamFactory;

		/*
		$resolver = new OptionsResolver();
		$resolver->setDefaults(
			[
				CURLOPT_HEADER => false,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_FOLLOWLOCATION => false
			]
		);

		// Our parsing will fail if this is set to true.
		$resolver->setAllowedValues(
			(string)CURLOPT_HEADER,
			[false]
		);

		// Our parsing will fail if this is set to true.
		$resolver->setAllowedValues(
			(string)CURLOPT_RETURNTRANSFER,
			[false]
		);

		// We do not know what everything curl supports and might support in the future.
		// Make sure that we accept everything that is in the options.
		$resolver->setDefined(array_keys($options));
		*/

		//$this->curlOptions = $resolver->resolve($options);

		$this->curlOptions = $options;
	}

	/**
	 * Release resources if still active.
	 */
	public function __destruct() {
		if (is_resource($this->handle)) {
            // @codeCoverageIgnoreStart
			@curl_close($this->handle);
            // @codeCoverageIgnoreEnd
		}
	}

	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 *
	 * @param HttpRequestInterface $request
	 *
	 * @return HttpResponseInterface
	 *
	 * @throws InvalidArgumentException  For invalid header names or values.
	 * @throws RuntimeException          If creating the body stream fails.
	 * @throws NetworkExceptionInterface In case of network problems.
	 * @throws RequestExceptionInterface On invalid request.
	 */
	public function sendRequest(HttpRequestInterface $request): HttpResponseInterface {
		$response                                   = $this->initResponse();
		$requestOptions                             = $this->prepareRequestOptions($request,
			$this->getHeaderFunction($response),
			$this->getBodyFunction($response),
		);

		if (is_resource($this->handle)) {
			curl_reset($this->handle);
		} else {
			$this->handle = curl_init();
		}

		curl_setopt_array($this->handle, $requestOptions);
		curl_exec($this->handle);

		$errno = curl_errno($this->handle);
		switch ($errno) {
			case CURLE_OK:
				// All OK, no actions needed.
				break;
			case CURLE_COULDNT_RESOLVE_PROXY:
			case CURLE_COULDNT_CONNECT:
			case CURLE_OPERATION_TIMEOUTED:
			case CURLE_SSL_CONNECT_ERROR:
            case CURLE_COULDNT_RESOLVE_HOST:
				throw new HttpClientNetworkException($request, curl_error($this->handle));
			default:
				throw new HttpClientRequestException($request, curl_error($this->handle));
		}

		$response->getBody()->seek(0);
		return $response;
	}

    /**
     * @param HttpResponseInterface $response
     * @return callable
     */
	protected function getHeaderFunction(HttpResponseInterface &$response): callable {
		return function ($ch, $data) use (&$response) {
			$str                                    = trim($data);
			if ($str !== '') {
				if (stripos($str, 'http/') === 0) {
					$parts                          = explode(' ', $str, 3);
					if (count($parts) < 2 || 0 !== strpos(strtolower($parts[0]), 'http/')) {
						throw new HttpClientInvalidHeaderException(
							sprintf('"%s" is not a valid HTTP status line', $str)
						);
					}
					$reasonPhrase                   = count($parts) > 2 ? $parts[2] : '';
					$response                       = $response
						->withStatus((int) $parts[1], $reasonPhrase)
						->withProtocolVersion(substr($parts[0], 5));
				} else {
					$parts                          = explode(':', $str, 2);
					if (count($parts) !== 2) {
						throw new HttpClientInvalidHeaderException(
							sprintf('"%s" is not a valid HTTP header line', $str)
						);
					}
					$name                           = trim($parts[0]);
					$value                          = trim($parts[1]);
					if ($response->hasHeader($name)) {
						$response                   = $response->withAddedHeader($name, $value);
					} else {
						$response                   = $response->withHeader($name, $value);
					}
				}
			}
			return strlen($data);
		};
	}

    /**
     * @param HttpResponseInterface $response
     * @return callable
     */
    protected function getBodyFunction(HttpResponseInterface $response): callable {
		return function($ch, $data) use ($response) {
			$response->getBody()->write($data);
			return strlen($data);
		};
	}

	/**
	 * Create builder to use for building response object.
	 *
	 * @return HttpResponseInterface
	 */
	protected function initResponse(): HttpResponseInterface {
		$body = $this->streamFactory->createStreamFromFile('php://temp', 'w+b');
		return $this->responseFactory
			->createResponse(200)
			->withBody($body);
	}

	/**
	 * Update cURL options for given request and hook in the response builder.
	 *
	 * @param HttpRequestInterface $request         HttpClientRequest on which to create options.
	 * @param callable $headerFunction
	 * @param callable $bodyFunction
	 *
	 * @return array cURL options based on request.
	 *
	 * @throws InvalidArgumentException  For invalid header names or values.
	 * @throws RuntimeException          If can not read body.
	 * @throws RequestExceptionInterface On invalid request.
	 */
	protected function prepareRequestOptions(
        HttpRequestInterface $request,
		callable $headerFunction,
		callable $bodyFunction
	): array {
		$curlOptions = $this->curlOptions;
		try {
			$curlOptions[CURLOPT_HTTP_VERSION]
				= $this->getProtocolVersion($request->getProtocolVersion());
		} catch (UnexpectedValueException $e) {
			throw new HttpClientRequestException($request, $e->getMessage());
		}
		$curlOptions[CURLOPT_URL]                   = (string)$request->getUri();
		$curlOptions                                = $this->addRequestBodyOptions($request, $curlOptions);
		$curlOptions[CURLOPT_HTTPHEADER]            = $this->createHeaders($request, $curlOptions);

		if ($request->getUri()->getUserInfo()) {
			$curlOptions[CURLOPT_USERPWD]           = $request->getUri()->getUserInfo();
		}
		$curlOptions[CURLOPT_HEADERFUNCTION]        = $headerFunction;
		$curlOptions[CURLOPT_WRITEFUNCTION]         = $bodyFunction;
		return $curlOptions;
	}

	/**
	 * Return cURL constant for specified HTTP version.
	 * @param string $requestVersion HTTP version ("1.0", "1.1" or "2.0").
	 * @return int Respective CURL_HTTP_VERSION_x_x constant.
	 * @throws HttpClientUnsupportedProtocolVersionException If unsupported version requested.
	 */
	protected function getProtocolVersion(string $requestVersion): int {
		switch ($requestVersion) {
			case '1.0':
				return CURL_HTTP_VERSION_1_0;
			case '1.1':
				return CURL_HTTP_VERSION_1_1;
			case '2.0':
				if (defined('CURL_HTTP_VERSION_2_0')) {
					return CURL_HTTP_VERSION_2_0;
				}
                // @codeCoverageIgnoreStart
				throw new HttpClientUnsupportedProtocolVersionException();
                // @codeCoverageIgnoreEnd
		}
		return CURL_HTTP_VERSION_NONE;
	}

	/**
	 * Add request body related cURL options.
	 *
	 * @param HttpRequestInterface  $request     HttpClientRequest on which to create options.
	 * @param array                 $curlOptions Options created by prepareRequestOptions().
	 *
	 * @return array cURL options based on request.
	 */
	protected function addRequestBodyOptions(HttpRequestInterface $request, array $curlOptions): array {
		/*
		 * Some HTTP methods cannot have payload:
		 *
		 * - GET — cURL will automatically change method to PUT or POST if we set CURLOPT_UPLOAD or
		 *   CURLOPT_POSTFIELDS.
		 * - HEAD — cURL treats HEAD as GET request with a same restrictions.
		 * - TRACE — According to RFC7231: a client MUST NOT send a message body in a TRACE request.
		 */
		if (!in_array($request->getMethod(), ['GET', 'HEAD', 'TRACE'], true)) {
			$body                                   = $request->getBody();
			$bodySize                               = $body->getSize();
			if ($bodySize !== 0) {
				if ($body->isSeekable()) {
					$body->rewind();
				}
				// Message has non empty body.
				if ($bodySize === null || $bodySize > 1024 * 1024) {
					// Avoid full loading large or unknown size body into memory
					$curlOptions[CURLOPT_UPLOAD]    = true;
					if (null !== $bodySize) {
						$curlOptions[CURLOPT_INFILESIZE] = $bodySize;
					}
					$curlOptions[CURLOPT_READFUNCTION] = function ($ch, $fd, $length) use ($body) {
						return $body->read($length);
					};
				} else {
					// Small body can be loaded into memory
					$curlOptions[CURLOPT_POSTFIELDS] = (string)$body;
				}
			}
		}
		if ($request->getMethod() === 'HEAD') {
			// This will set HTTP method to "HEAD".
			$curlOptions[CURLOPT_NOBODY]            = true;
		} elseif ($request->getMethod() !== 'GET') {
			// GET is a default method. Other methods should be specified explicitly.
			$curlOptions[CURLOPT_CUSTOMREQUEST]     = $request->getMethod();
		}
		return $curlOptions;
	}

	/**
	 * Create headers array for CURLOPT_HTTPHEADER.
	 *
	 * @param HttpRequestInterface  $request     HttpClientRequest on which to create headers.
	 * @param array                 $curlOptions Options created by prepareRequestOptions().
	 *
	 * @return string[]
	 */
	private function createHeaders(HttpRequestInterface $request, array $curlOptions): array {
		$curlHeaders                                = [];
		$headers                                    = $request->getHeaders();
		foreach ($headers as $name => $values) {
			$header                                 = strtolower($name);
			if ($header === 'expect') {
				// curl-client does not support "Expect-Continue", so dropping "expect" headers
				continue;
			}
			if ($header === 'content-length') {
				if (array_key_exists(CURLOPT_POSTFIELDS, $curlOptions)) {
					// Small body content length can be calculated here.
					$values = [strlen($curlOptions[CURLOPT_POSTFIELDS])];
				} elseif (!array_key_exists(CURLOPT_READFUNCTION, $curlOptions)) {
					// Else if there is no body, forcing "Content-length" to 0
					$values = [0];
				}
			}
			foreach ($values as $value) {
				$curlHeaders[] = $name . ': ' . $value;
			}
		}
		/*
		 * curl-client does not support "Expect-Continue", but cURL adds "Expect" header by default.
		 * We can not suppress it, but we can set it to empty.
		 */
		$curlHeaders[] = 'Expect:';

		return $curlHeaders;
	}

}