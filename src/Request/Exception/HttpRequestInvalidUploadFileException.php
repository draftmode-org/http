<?php
namespace Terrazza\Component\Http\Request\Exception;
use InvalidArgumentException;
use Throwable;

class HttpRequestInvalidUploadFileException extends InvalidArgumentException {
    public function __construct($message = "", Throwable $previous = null) {
        parent::__construct($message, 400, $previous);
    }
}