<?php
namespace Terrazza\Component\Http\Client\Exception;
use UnexpectedValueException;

class HttpClientUnsupportedProtocolVersionException extends UnexpectedValueException {
    public function __construct() {
        parent::__construct("libcurl 7.33 needed for HTTP 2.0 support");
    }
}