<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\IHttpResponse;

interface IHttpRequestMiddleware {
    public function handle(IHttpClientRequest $request, IHttpRequestHandler $requestHandler): IHttpResponse;
}