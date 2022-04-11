<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpRequestHandlerInterface {
    /**
     * @param HttpRequestInterface $request
     * @return HttpResponseInterface
     */
    public function handle(HttpRequestInterface $request): HttpResponseInterface;
}