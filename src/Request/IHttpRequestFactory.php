<?php

namespace Terrazza\Component\Http\Request;

interface IHttpRequestFactory {
    public function getServerRequest() : IHttpServerRequest;
}