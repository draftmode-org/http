<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Request;
use Terrazza\Component\Http\Message\HttpMessageAdapter;

class HttpServerRequestHandler implements HttpServerRequestHandlerInterface {

    public function handle(HttpRequestHandlerInterface $requestHandler): void {
        $messageAdapter                             = new HttpMessageAdapter();
        $serverRequest                              = $messageAdapter->getServerRequestFromGlobals();
        //
        $response                                   = $requestHandler->handle($serverRequest);
        $messageAdapter->emitResponse($response);
    }
}