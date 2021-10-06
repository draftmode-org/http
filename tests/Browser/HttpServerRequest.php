<?php
require_once ("../../plugin/autoload.php");
use Terrazza\Component\Http\Message\HttpMessageAdapter;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

$request = (new HttpMessageAdapter)->getServerRequestFromGlobals(
    new HttpStreamFactory()
);

echo "<pre>";print_r(
    [
        "method" => $request->getMethod(),
        "protocolVersion" => $request->getProtocolVersion(),
        "requestTarget" => $request->getRequestTarget(),
        "queryParams" => $request->getQueryParams(),
        "uri" => [
            "scheme" => $request->getUri()->getScheme(),
            "host" => $request->getUri()->getHost(),
            "path" => $request->getUri()->getPath(),
            "query" => $request->getUri()->getQuery(),
        ]
    ]
);echo "</pre>";
