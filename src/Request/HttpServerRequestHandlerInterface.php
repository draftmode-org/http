<?php
namespace Terrazza\Component\Http\Request;

interface HttpServerRequestHandlerInterface {
    /**
     * @param HttpRequestHandlerInterface $requestHandler
     */
    public function handle(HttpRequestHandlerInterface $requestHandler): void;
}