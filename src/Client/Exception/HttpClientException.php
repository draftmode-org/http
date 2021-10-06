<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Client\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

class HttpClientException extends RuntimeException implements ClientExceptionInterface {

	private RequestInterface $request;

	function __construct(RequestInterface $request, $message = "", $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->request = $request;
	}

	public function getRequest(): RequestInterface {
		return $this->request;
	}
	
}