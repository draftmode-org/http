<?php

namespace Terrazza\Component\Http\Request;

use Terrazza\Component\Http\Message\HttpMessageAdapter;
use Terrazza\Component\Http\Stream\IHttpStreamFactory;

class HttpRequestFactory implements IHttpRequestFactory {
    private IHttpStreamFactory $streamFactory;
    public function __construct(IHttpStreamFactory $streamFactory) {
        $this->streamFactory                        = $streamFactory;
    }

    public function getServerRequest() : IHttpServerRequest {
        $httpMessageAdapter                         = new HttpMessageAdapter();
        return $httpMessageAdapter->getServerRequestFromGlobals($this->streamFactory);
    }
}