<?php
namespace Terrazza\Component\Http\Request\Exception;
use Throwable;
use UnexpectedValueException;

class HttpRequestUnexpectedBodyException extends UnexpectedValueException {
    public function __construct($message = "", Throwable $previous = null) {
        parent::__construct($message, 400, $previous);
    }
}