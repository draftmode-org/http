<?php
namespace Terrazza\Component\Http\Middleware;
use Terrazza\Component\Http\Request\HttpRequestHandlerInterface;

interface HttpServerRequestMiddlewareHandlerInterface {
    /**
     * @param HttpRequestHandlerInterface $requestHandler
     */
    public function handle(HttpRequestHandlerInterface $requestHandler): void;
}