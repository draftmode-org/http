<?php
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpRequestHandlerInterface {
    public function handle(HttpRequestInterface $request): HttpResponseInterface;
}