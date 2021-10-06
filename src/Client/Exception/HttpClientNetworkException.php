<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Client\Exception;
use Psr\Http\Client\NetworkExceptionInterface;

class HttpClientNetworkException extends HttpClientException implements NetworkExceptionInterface {}