<?php
namespace Terrazza\Component\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;

interface HttpResponseFactoryInterface extends ResponseFactoryInterface {
    /**
     * @param HttpResponseInterface $response
     */
    public function emitResponse(HttpResponseInterface $response) : void;

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return HttpResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = '') : HttpResponseInterface;

    /**
     * @param int $responseCode
     * @param $content
     * @return HttpResponseInterface
     */
    public function createJsonResponse(int $responseCode, $content) : HttpResponseInterface;
}