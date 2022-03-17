<?php
namespace Terrazza\Component\Http\Client;
use Terrazza\Component\Http\Request\HttpRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpClientInterface {
    /**
     * @param HttpRequestInterface $request
     * @return HttpResponseInterface
     */
    public function sendRequest(HttpRequestInterface $request): HttpResponseInterface;
}