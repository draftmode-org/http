<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\IHttpResponse;

interface IHttpRequestFactory {
    public function execute(IHttpClientRequest $request) : IHttpResponse;
}