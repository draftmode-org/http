<?php
namespace Terrazza\Component\Http\Message;
use Terrazza\Component\Http\Request\HttpServerRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;

interface HttpMessageAdapterInterface {
    /**
     * @return HttpServerRequestInterface
     */
    public function getServerRequestFromGlobals(): HttpServerRequestInterface;

    /**
     * @param HttpResponseInterface $response
     */
    public function emitResponse(HttpResponseInterface $response): void;
}