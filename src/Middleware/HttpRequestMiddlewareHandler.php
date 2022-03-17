<?php
namespace Terrazza\Component\Http\Middleware;
use Generator;
use Terrazza\Component\Http\Message\HttpMessageAdapter;
use Terrazza\Component\Http\Request\HttpRequestHandlerInterface;
use Terrazza\Component\Http\Request\HttpRequestInterface;
use Terrazza\Component\Http\Response\HttpResponseInterface;

class HttpRequestMiddlewareHandler implements HttpRequestMiddlewareHandlerInterface {
    /**
     * @var array|HttpRequestMiddlewareInterface[]
     */
    private array $middlewares;
    public function __construct(array $middlewares){
        $this->middlewares                          = $middlewares ?? [];
    }

    /**
     * @return Generator
     */
    private function getGenerator() : Generator {
        foreach ($this->middlewares as $middleware) {
            yield $middleware;
        }
    }

    public function handle(HttpRequestInterface $request, HttpRequestHandlerInterface $requestHandler): void {
        //
        // middleware concept
        //
        $response                                   = (new class ($this->getGenerator(), $requestHandler) implements HttpRequestHandlerInterface {
            private Generator $generator;
            private HttpRequestHandlerInterface $requestHandler;

            public function __construct(Generator $generator, HttpRequestHandlerInterface $requestHandler) {
                $this->generator                    = $generator;
                $this->requestHandler               = $requestHandler;
            }

            public function handle(HttpRequestInterface $request) : HttpResponseInterface {
                if (!$this->generator->valid()) {
                    return $this->requestHandler->handle($request);
                }
                /** @var HttpRequestMiddlewareInterface $current */
                $current                            = $this->generator->current();
                $this->generator->next();
                return $current->handle($request, $this);
            }
        })->handle($request);
        //
        // emit response
        //
        $messageAdapter                             = new HttpMessageAdapter();
        $messageAdapter->emitResponse($response);
    }
}