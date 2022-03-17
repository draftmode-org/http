<?php
namespace Terrazza\Component\Http\Middleware;
use Terrazza\Component\Http\Request\HttpRequestHandlerInterface;
use Terrazza\Component\Http\Request\HttpRequestInterface;

interface HttpRequestMiddlewareHandlerInterface {
    /**
     * @param HttpRequestInterface $request
     * @param HttpRequestHandlerInterface $requestHandler
     */
    public function handle(HttpRequestInterface $request, HttpRequestHandlerInterface $requestHandler): void;
}