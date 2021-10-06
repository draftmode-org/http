<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\IHttpResponse;

interface IHttpRequestHandler {
    public function handle(IHttpClientRequest $request): IHttpResponse;
}