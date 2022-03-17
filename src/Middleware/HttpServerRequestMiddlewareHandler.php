<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Middleware;
use Terrazza\Component\Http\Message\HttpMessageAdapter;
use Terrazza\Component\Http\Request\HttpRequestHandlerInterface;

class HttpServerRequestMiddlewareHandler implements HttpServerRequestMiddlewareHandlerInterface {
    private HttpRequestMiddlewareHandlerInterface $requestFactory;

    public function __construct(HttpRequestMiddlewareHandlerInterface $requestFactory) {
        $this->requestFactory                       = $requestFactory;
    }

    public function handle(HttpRequestHandlerInterface $requestHandler): void {
        $messageAdapter                             = new HttpMessageAdapter();
        $serverRequest                              = $messageAdapter->getServerRequestFromGlobals();
        //
        $this->requestFactory->handle($serverRequest, $requestHandler);
    }
}