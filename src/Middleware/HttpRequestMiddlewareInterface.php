<?php
namespace Terrazza\Component\Http\Middleware;
use Terrazza\Component\Http\Request\HttpRequestHandlerInterface;
use Terrazza\Component\Http\Request\HttpRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpRequestMiddlewareInterface {
    /**
     * @param HttpRequestInterface $request
     * @param HttpRequestHandlerInterface $requestHandler
     * @return HttpResponseInterface
     */
    public function handle(HttpRequestInterface $request, HttpRequestHandlerInterface $requestHandler): HttpResponseInterface;
}