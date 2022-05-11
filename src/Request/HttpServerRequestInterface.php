<?php
namespace Terrazza\Component\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

interface HttpServerRequestInterface extends ServerRequestInterface, HttpRequestInterface {
    //
    public function getServerParam(string $paramKey) :?string;
    public function getCookieParam(string $paramKey) :?string;
    //
    public function getPathParam(string $routeUri, string $argumentName) :?string;
    public function getPathParams(string $routeUri) : array;
    public function getQueryParam(string $argumentName) :?string;
    //
    public function isValidBody() : void;
}