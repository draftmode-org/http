# the http component
This component is for any interaction with/from Http

1. Object/Classes
   1. [Client](#structure-client)
   2. [Message](#structure-message)
   3. [Request](#structure-request)
   4. [Response](#structure-response)
   5. [Stream](#structure-stream)
2. [Install](#install)
3. [Requirements](#require)
## Object/Classes
<a id="structure-client" name="structure-client"></a>
<a id="user-content-structure-client" name="user-content-structure-client"></a>
### Client
#### HttpClient
common psr interface implementation.

<a id="structure-message" name="structure-message"></a>
<a id="user-content-structure-message" name="user-content-structure-message"></a>
### Message
#### Uri
common psr interface implementation.
#### HttpMessageAdapter
..description missing
#### HttpMessageHelper
..description missing

<a id="structure-request" name="structure-request"></a>
<a id="user-content-structure-request" name="user-content-structure-request"></a>
### Request
implementations are really close to the common psr interfaces.<br>
the differences are explained.
#### HttpClientRequest (RequestInterface)
- public function withContentType(string $contentType)
- public function withContent(string $contentType, $body)
- public function getPathParam(string $routeUri, string $argumentName)
- public function getQueryParam(string $argumentName)
- public function withQueryParams(array $query)<br>
  <i>request->uri->query will be also updated</i>
- public function withUploadedFiles(array $uploadFiles)
- public function withUploadedFile(string $formDataName, UploadedFileInterface $uploadedFile)<br><br>
#### HttpRequestFactory
HttpRequestFactory covers some HttpRequest methods.
##### method: getServerRequest() : IHttpServerRequest
#### HttpRequestHelper (Trait)
trait for common methods used in HttpRequest and HttpServerRequest.
#### HttpRequestMiddleware
HttpRequestMiddleware provides a middleware implementation.
#### HttpServerRequest (ServerRequestInterface)
##### method: getServerParam(string $paramKey)
##### method: getCookieParam(string $paramKey)
##### method: getPathParam(string $routeUri, string $argumentName)
##### method: getQueryParam(string $argumentName)
##### method: withQueryParams(array $queryParams)
request->uri->query will be also updated
##### method: isValidBody()
validate Content-Type against ->body
#### HttpServerRequest
..description missing

<a id="structure-response" name="structure-response"></a>
<a id="user-content-structure-response" name="user-content-structure-response"></a>
### Response
all implementations are really close to psr interfaces. only the differences explained.
#### HttpResponse (ResponseInterface)
HttpResponse implements the common psr interface.<br>
#### HttpResponseFactory (ResponseFactoryInterface)
HttpResponseFactory implements the psr interface and
##### method: createJsonResponse(int $responseCode, $content)

<a id="structure-stream" name="structure-stream"></a>
<a id="user-content-structure-stream" name="user-content-structure-stream"></a>
### Stream
#### HttpStream
HttpStream implements the psr interface and
- __constructor<br>
  <i>is public and the class can be used directly</i>
##### method: getContents(bool $rewindAfterReading=false)
rewindAfterReading set stream->seek(0) to reuse the content
##### method: setMediaType(string $mediaType)
set mime_type. StreamFactory->createStreamFromFile will do this automatically
##### method: getMediaType()
##### method: setFileName(string $fileName)
set fileName. StreamFactory->createStreamFromFile will do this automatically
##### method: getFileName()

#### HttpStreamFactory
HttpStreamFactory create the HttpStream directly without using any static functions. That allows us to inject the factory.
<br><br>
##### method: createStreamFromFile</b><br>
set, in case of a touchable file, mimeType and fileName to the HttpStream Object (IHttpStream).<br>
#### UploadFile
- private function setStreamOrFile($streamOrFile)
  <br><i>streamOrFile<br>
  . is a touchable file<br>
  . or an instanceof IHttpStream<br>
  ClientMediaType and ClientFilename are set, if present.</i><br>

<a name="install"></a>
## Install
### Install via composer
```
composer require terrazza/component-http
```

<a name="require"></a>
## Requirements
### php version
- \>= 7.4
### php extension
- ext-curl
- ext-json
- ext-fileinfo