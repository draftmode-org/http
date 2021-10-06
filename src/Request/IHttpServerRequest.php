<?php
namespace Terrazza\Component\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

interface IHttpServerRequest extends ServerRequestInterface {
    //
    public function getServerParam(string $paramKey) :?string;
    public function getCookieParam(string $paramKey) :?string;
    //
    public function getPathParam(string $routeUri, string $argumentName) :?string;
    public function getQueryParam(string $argumentName) :?string;
    //
    public function isValidBody() : void;
}