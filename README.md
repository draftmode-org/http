# the http component
This component is for any interaction with/from Http

1. [How to install](#install)
2. [Requirements](#require)
3. [Structure](#structure)
   1. [Client](#structure-client)
   2. [Message](#structure-message)
   3. [Request](#structure-request)
   4. [Response](#structure-response)
   5. [Stream](#structure-stream)

<a name="install"></a>
## How to install
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

<a name="structure"></a>
## Structure

<a name="structure-client"></a>
## Client
### HttpClient
common psr interface implementation.

<a name="structure-message"></a>
## Message
### Uri
common psr interface implementation.
### HttpMessageAdapter
..
### HttpMessageHelper
..

<a name="structure-request"></a>
## Request
implementations are really close to the common psr interfaces.<br>
the differences are explained. 
### HttpClientRequest (RequestInterface)
- public function withContentType(string $contentType)
- public function withContent(string $contentType, $body)
- public function getPathParam(string $routeUri, string $argumentName)
- public function getQueryParam(string $argumentName)
- public function withQueryParams(array $query)<br>
  <i>request->uri->query will be also updated</i>
- public function withUploadedFiles(array $uploadFiles)
- public function withUploadedFile(string $formDataName, UploadedFileInterface $uploadedFile)<br><br>
### HttpRequestMiddleware
HttpRequestMiddleware provides a middleware implementation.
### HttpRequestHelper (trait)
trait for common methods used in HttpRequest and HttpServerRequest.
### HttpServerRequest (ServerRequestInterface)
- public function getServerParam(string $paramKey)
- public function getCookieParam(string $paramKey)
- public function getPathParam(string $routeUri, string $argumentName)
- public function getQueryParam(string $argumentName)
- public function withQueryParams(array $queryParams)<br>
  <i>request->uri->query will be also updated</i>
- public function isValidBody() : void<br>
  <i>validate Content-Type against ->body</i>

<a name="structure-response"></a> 
## Response
all implementations are really close to psr interfaces. only the differences explained.
### HttpResponse (ResponseInterface)
### HttpResponseFactory (ResponseFactoryInterface)
- public function createJsonResponse(int $responseCode, $content)

<a name="structure-stream"></a>
## Stream
### HttpStream
- __constructor<br>
  <i>is public and the class can be used directly</i>
- public function getContents(bool $rewindAfterReading=false)<br>
  <i>rewindAfterReading set stream->seek(0) to reuse the content</i>
- public function setMediaType(string $mediaType)<br>
  <i>set mime_type. StreamFactory->createStreamFromFile will do this automatically</i>
- public function getMediaType()  
- public function setFileName(string $fileName)<br>
  <i>set fileName. StreamFactory->createStreamFromFile will do this automatically</i>
- public function getFileName()
### HttpStreamFactory
HttpStreamFactory create the HttpStream directly without using any static functions. That allows us to inject the factory.
<br><br>
<b>method: createStreamFromFile</b><br>
set, in case of a touchable file, mimeType and fileName to the HttpStream Object (IHttpStream).<br>
### UploadFile
- private function setStreamOrFile($streamOrFile)
<br><i>streamOrFile<br>
. is a touchable file<br>
. or an instanceof IHttpStream<br>
ClientMediaType and ClientFilename are set, if present.</i><br>