<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Client\Exception;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Terrazza\Component\Http\Request\HttpRequestInterface;
use Throwable;

class HttpClientException extends RuntimeException implements ClientExceptionInterface {

	private HttpRequestInterface $request;

	function __construct(HttpRequestInterface $request, $message = "", $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->request = $request;
	}

	public function getRequest(): HttpRequestInterface {
		return $this->request;
	}
	
}