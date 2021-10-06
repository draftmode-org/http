<?php
namespace Terrazza\Component\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;

interface IHttpResponseFactory extends ResponseFactoryInterface {
    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return IHttpResponse
     */
    public function createResponse(int $code = 200, string $reasonPhrase = '') : IHttpResponse;

    /**
     * @param int $responseCode
     * @param $content
     * @return IHttpResponse
     */
    public function createJsonResponse(int $responseCode, $content) : IHttpResponse;
}