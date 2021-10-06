<?php
namespace Terrazza\Component\Http\Message;
use Psr\Http\Message\ResponseInterface;
use Terrazza\Component\Http\Request\IHttpServerRequest;
use Terrazza\Component\Http\Stream\IHttpStreamFactory;

interface IHttpMessageAdapter {
    /**
     * @param IHttpStreamFactory $streamFactory
     * @return IHttpServerRequest
     */
    public function getServerRequestFromGlobals(IHttpStreamFactory $streamFactory): IHttpServerRequest;

    /**
     * @param ResponseInterface $response
     */
    public function emitResponse(ResponseInterface $response): void;
}