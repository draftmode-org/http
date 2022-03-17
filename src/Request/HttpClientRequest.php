<?php
declare(strict_types=1);
namespace Terrazza\Component\Http\Request;

use Terrazza\Component\Http\Message\HttpMessageHelper;
use Terrazza\Component\Http\Message\Uri\Uri;
use Terrazza\Component\Http\Request\Exception\HttpRequestInvalidUploadFileException;
use Terrazza\Component\Http\Request\Exception\HttpRequestUnexpectedBodyException;
use Terrazza\Component\Http\Stream\HttpStreamInterface;
use Terrazza\Component\Http\Stream\HttpStreamFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * PSR-7 request implementation.
 */
class HttpClientRequest implements HttpClientRequestInterface {
    use HttpMessageHelper;
    use HttpRequestHelper;

    /** @var string|null */
    private ?string $requestTarget = null;

	/**
	 * @param string $method HTTP method
	 * @param string|UriInterface $uri URI
	 * @param array $headers HttpClientRequest headers
	 * @param string|resource|StreamInterface|array|null $body HttpClientRequest body
	 * @param string $version ProtocolVersion version
	 */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
	    $this->initializeRequest($method, $uri, $headers, $body, $version);
	    if ($this->uri->getQuery() !== '') {
	        $this->parseQueryParam($this->uri);
        }
    }

    protected function parseQueryParam(Uri $uri) {
        parse_str($uri->getQuery(), $params);
        if (count($params)) {
            $this->queryParams                      = $params;
            $this->uri->withQuery("");
        }
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function withContentType(string $contentType) :self {
        return (clone $this)
            ->withAddedHeader("Content-Type", $contentType);
    }

    /**
     * @param string $contentType
     * @param string|resource|StreamInterface|array|null $body HttpClientRequest body
     * @return $this
     */
    public function withContent(string $contentType, $body) : self {
        $request                                    = (clone $this)
            ->withAddedHeader("Content-Type", $contentType);
        $request->body                              = $body;
        return $request;
    }

    /**
     * @return HttpStreamInterface
     */
    public function getBody(): HttpStreamInterface {
        if ($this->stream) {
            return $this->stream;
        }
        //
        // protected method
        //
        if (in_array($this->getMethod(), ['GET', 'HEAD', 'TRACE'], true)) {
            return (new HttpStreamFactory)->createStream();
        }
        $contentType                                = $this->getHeaderLine("Content-Type");
        if (preg_match("#application/x-www-form-urlencoded#", $contentType)) {
            if (is_array($this->body) || is_object($this->body)) {
                $content                            = http_build_query($this->body);
            } else {
                throw new HttpRequestUnexpectedBodyException("content must be of the type array or object, given " . gettype($this->body));
            }
        }
        elseif (preg_match("#application/json#", $contentType)) {
            //
            // cannot produce a json_encode exception
            //
            try {
                if ($this->body === null) {
                    $content                        = "";
                } else {
                    $content                        = json_encode($this->body, JSON_THROW_ON_ERROR); // | JSON_PRETTY_PRINT);
                }
            }
            // @codeCoverageIgnoreStart
            catch (Throwable $exception) {
                throw new HttpRequestUnexpectedBodyException("content could not be json encoded", $exception);
            }
            // @codeCoverageIgnoreEnd
        }
        elseif (preg_match("#multipart/form-data#", $contentType)) {
            $boundary                               = md5(serialize([
                "datetime"                          => date("U"),
                "method"                            => $this->getMethod(),
                "requestTarget"                     => $this->getRequestTarget(),
                "body"                              => $this->body,
                "files"                             => $this->uploadFiles
            ]));
            if ($this->body) {
                if (is_array($this->body)) {
                    $content                        = $this->getFormDataContent($boundary, $this->body);
                } else {
                    throw new HttpRequestUnexpectedBodyException("content must be of the type array, given " . gettype($this->body));
                }
            } else {
                $content                            = "";
            }
            $content                                .= $this->getUploadFileContent($boundary);
            if (strlen($content) === 0) {
                throw new HttpRequestUnexpectedBodyException("content must include either a body or uploadFiles");
            }
        } else {
            if (is_string($this->body)) {
                $content                            = $this->body;
            } elseif (is_null($this->body)) {
                $content                            = "";
            } else {
                throw new HttpRequestUnexpectedBodyException("body content type cannot be used, given ".gettype($this->body));
            }
        }
       return (new HttpStreamFactory)->createStream($content);
    }

    /**
     * @param string $boundary
     * @return string
     */
    private function getUploadFileContent(string $boundary) : string {
        $content                                    = "";
        foreach ($this->uploadFiles ?? [] as $kFileKey => $uploadFile) {
            if (is_array($uploadFile)) {
                foreach ($uploadFile as $uploadSingleFile) {
                    if ($uploadSingleFile instanceof UploadedFileInterface) {
                        $content                    .= $this->getFormDataUploadFileContent($boundary, $kFileKey."[]", $uploadSingleFile);
                    } else {
                        throw new HttpRequestInvalidUploadFileException("expected uploadFileInterface");
                    }
                }
            } else {
                if ($uploadFile instanceof UploadedFileInterface) {
                    $content                        .= $this->getFormDataUploadFileContent($boundary, $kFileKey, $uploadFile);
                } else {
                    throw new HttpRequestInvalidUploadFileException("expected uploadFileInterface");
                }
            }
        }
        return $content;
    }

    /**
     * @param string $boundary
     * @param string $formDataName
     * @param UploadedFileInterface $uploadedFile
     * @return string
     */
    private function getFormDataUploadFileContent(string $boundary, string $formDataName, UploadedFileInterface $uploadedFile) : string {
        return "--{$boundary}" .
            "\r\n".
            "Content-Disposition: form-data; name=\"$formDataName\"; filename=\"".basename($uploadedFile->getClientFilename())."\"" . "\r\n".
            "Content-Length: ".$uploadedFile->getStream()->getSize() .
            "\r\n" .
            "\r\n" .
            $uploadedFile->getStream()->getContents() .
            "\r\n";
    }

    /**
     * @param string $boundary
     * @param array|null $body
     * @return string
     */
    private function getFormDataContent(string $boundary, ?array $body=null) : string {
        $content                                    = "";
        foreach ($body ?? [] as $bKey => $bValue) {
            if (is_array($bValue)) {
                foreach ($bValue as $bSingleValue) {
                    $content .= "--{$boundary}" .
                        "\r\n" .
                        "Content-Disposition: form-data; name=\"{$bKey}[]\"" .
                        "\r\n" .
                        "\r\n" .
                        "{$bSingleValue}" .
                        "\r\n";
                }
            } else {
                $content .= "--{$boundary}" .
                    "\r\n" .
                    "Content-Disposition: form-data; name=\"$bKey\"" .
                    "\r\n" .
                    "\r\n" .
                    "{$bValue}" .
                    "\r\n";
            }
        }
        return $content;
    }
}
