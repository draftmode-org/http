<?php
namespace Terrazza\Component\Http\Request;

use Terrazza\Component\Http\Response\IHttpResponse;
use Generator;
use Psr\Http\Client\ClientInterface;

class HttpRequestMiddleware implements IHttpRequestMiddlewareFactory {
    /** @var IHttpRequestMiddleware[] */
    private array $middlewares;

    /** @var ClientInterface  */
    private ClientInterface $httpClient;

    /**
     * HttpRequestMiddleware constructor.
     * @param ClientInterface $httpClient
     * @param IHttpRequestMiddleware[] $middlewares
     */
    public function __construct(ClientInterface $httpClient, IHttpRequestMiddleware ...$middlewares) {
        $this->httpClient                           = $httpClient;
        $this->middlewares                          = $middlewares;
    }

    /**
     * @return Generator
     */
    private function getGenerator() : Generator {
        foreach ($this->middlewares as $middleware) {
            yield $middleware;
        }
    }

    /**
     * @param IHttpClientRequest $request
     * @return IHttpResponse
     */
    public function execute(IHttpClientRequest $request) : IHttpResponse {
        return (new class ($this->getGenerator(), $this->httpClient) implements IHttpRequestHandler {
            private Generator $generator;
            private ClientInterface $httpClient;

            public function __construct(Generator $generator, ClientInterface $httpClient) {
                $this->generator                    = $generator;
                $this->httpClient                   = $httpClient;
            }

            public function handle(IHttpClientRequest $request) : IHttpResponse {
                if (!$this->generator->valid()) {
                    return $this->httpClient->sendRequest($request);
                }
                /** @var IHttpRequestMiddleware $current */
                $current                            = $this->generator->current();
                $this->generator->next();
                return $current->handle($request, $this);
            }
        })->handle($request);
    }
}