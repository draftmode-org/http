<?php
namespace Terrazza\Component\Http\Client\Exception;
use InvalidArgumentException;

class HttpClientInvalidHeaderException extends InvalidArgumentException {
    public function __construct(string $message) {
        parent::__construct($message);
    }
}