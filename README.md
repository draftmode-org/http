# The Http Component
This component is for any interaction with/from Http

required PHP extensions:
- ext-curl
- ext-json
- ext-fileinfo

this project implements

- psr/http-message
- psr/http-client
- psr/http-factory
- psr/http-server-handler

and is structured
- src/Client
- src/Message
- src/Request
- src/Response
- src/Stream
## Client
### HttpClient
common psr interface implementation.
## Request
implementations are really close to the common psr interfaces.<br>
the differences are explained. 
### HttpClientRequest (RequestInterface)
- public function withContentType(string $contentType)<br><br>
- public function withContent(string $contentType, $body)<br><br>
- public function getPathParam(string $routeUri, string $argumentName)<br><br>
- public function getQueryParam(string $argumentName)<br><br>
- public function withQueryParams(array $query)<br>
  <i>request->uri->query will be also updated</i><br><br>
- public function withUploadedFiles(array $uploadFiles)<br><br>
- public function withUploadedFile(string $formDataName, UploadedFileInterface $uploadedFile)<br><br>
### HttpRequestMiddleware
HttpRequestMiddleware provides a middleware implementation.
### HttpRequestHelper (trait)
trait for common methods used in HttpRequest and HttpServerRequest.
### HttpServerRequest (ServerRequestInterface)
- public function getServerParam(string $paramKey)<br><br>
- public function getCookieParam(string $paramKey)<br><br>
- public function getPathParam(string $routeUri, string $argumentName)<br><br>
- public function getQueryParam(string $argumentName)
- public function withQueryParams(array $queryParams)<br>
  <i>request->uri->query will be also updated</i><br><br>
## Response
all implementations are really close to psr interfaces. only the differences explained.
### HttpResponse (ResponseInterface)
### HttpResponseFactory (ResponseFactoryInterface)
- public function createJsonResponse(int $responseCode, $content)
## Stream
### HttpStream
- __constructor<br>
  <i>is public and the class can be used directly</i><br><br>
- public function getContents(bool $rewindAfterReading=false)<br>
  <i>rewindAfterReading set stream->seek(0) to reuse the content</i><br><br>
- public function setMediaType(string $mediaType)<br>
  <i>set mime_type. StreamFactory->createStreamFromFile will do this automatically</i><br><br>
- public function getMediaType()<br><br>  
- public function setFileName(string $fileName)<br>
  <i>set fileName. StreamFactory->createStreamFromFile will do this automatically</i><br><br>
- public function getFileName()<br>
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