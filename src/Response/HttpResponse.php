<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Response;

use Terrazza\Component\Http\Message\HttpMessageHelper;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 response implementation.
 */
class HttpResponse implements HttpResponseInterface {
    use HttpMessageHelper;

    /** Map of standard HTTP status code/reason phrases */
    private const RESPONSE_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'HttpClientRequest Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'HttpClientRequest Entity Too Large',
        414 => 'HttpClientRequest-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'HttpClientRequest Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /** @var string */
	private string $reasonPhrase;

    /** @var int */
	private int $statusCode;

	/**
	 * @param int $status Status code
	 * @param array $headers Response headers
	 * @param string|resource|StreamInterface|null $body Response body
	 * @param string $version Protocol version
	 * @param string|null $reason Reason phrase (when empty a default will be used based on the status code)
	 */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        $this->assertStatusCodeRange($status);

        $this->statusCode = $status;

        if ($body instanceof StreamInterface) {
            $this->stream   = $body;
        } elseif (is_resource($body)) {
            $this->stream   = (new HttpStreamFactory)->createStreamFromResource($body);
        } elseif (is_string($body)) {
            $this->stream   = (new HttpStreamFactory())->createStream($body);
        }
        else {
            $this->body     = $body;
        }

        $this->setHeaders($headers);

        if ($reason == '' && isset(self::RESPONSE_PHRASES[$this->statusCode])) {
            $this->reasonPhrase = self::RESPONSE_PHRASES[$this->statusCode];
        } else {
            $this->reasonPhrase = (string) $reason;
        }

        $this->protocolVersion = $version;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string {
        return $this->reasonPhrase;
    }

	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return static
	 */
    public function withStatus($code, $reasonPhrase = ''): self {
        $this->assertStatusCodeIsInteger($code);
        $code = (int)$code;
        $this->assertStatusCodeRange($code);
		//
        $this->statusCode = $code;
        if ($reasonPhrase == '' && array_key_exists($this->statusCode, self::RESPONSE_PHRASES)) {
            $reasonPhrase = self::RESPONSE_PHRASES[$this->statusCode];
        }
        $this->reasonPhrase = (string)$reasonPhrase;
        return $this;
    }

    /**
     * @param mixed $statusCode
     */
    private function assertStatusCodeIsInteger($statusCode): void {
        if (filter_var($statusCode, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('status code must be an integer value.');
        }
    }

    private function assertStatusCodeRange(int $statusCode): void {
        if ($statusCode < 100 || $statusCode >= 600) {
            throw new InvalidArgumentException('status code must be an integer value between 1xx and 5xx.');
        }
    }
}
