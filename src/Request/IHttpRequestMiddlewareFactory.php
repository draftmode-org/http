<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\IHttpResponse;

interface IHttpRequestMiddlewareFactory {
    public function execute(IHttpClientRequest $request) : IHttpResponse;
}