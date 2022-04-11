<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpServerRequestBuilderInterface {
    /**
     * @param HttpRequestHandlerInterface $requestHandler
     * @return HttpResponseInterface
     */
    public function handle(HttpRequestHandlerInterface $requestHandler): HttpResponseInterface;

    /**
     * @return HttpServerRequestInterface
     */
    public function getServerRequest() : HttpServerRequestInterface;
}