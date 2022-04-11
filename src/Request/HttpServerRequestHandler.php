<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\HttpResponseInterface;

class HttpServerRequestHandler implements HttpServerRequestHandlerInterface {
    /**
     * @param HttpServerRequestInterface $request
     * @param HttpRequestHandlerInterface $requestHandler
     * @return HttpResponseInterface
     */
    public function handle(HttpRequestInterface $request, HttpRequestHandlerInterface $requestHandler): HttpResponseInterface {
        return $requestHandler->handle($request);
    }
}