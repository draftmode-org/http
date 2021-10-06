<?php
require_once ("../../plugin/autoload.php");

use Terrazza\Component\Http\Client\HttpClient;
use Terrazza\Component\Http\Request\HttpClientRequest;
use Terrazza\Component\Http\Response\HttpResponseFactory;
use Terrazza\Component\Http\Stream\HttpStreamFactory;

$request    = (new HttpClientRequest("GET", "http://www.google.at", [], "data"));
$client     = new HttpClient(
    new HttpResponseFactory(),
    new HttpStreamFactory()
);
$response   = $client->sendRequest($request);

echo "<pre>";print_r(
    [
        "method" => $response->getStatusCode(),
        "headers" => $response->getHeaders()
    ]
);echo "</pre>";