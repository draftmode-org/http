<?php
namespace Terrazza\Component\Http\Response;
use JsonSerializable;
use Terrazza\Component\Http\Message\HttpMessageAdapter;
use UnexpectedValueException;

class HttpResponseFactory implements HttpResponseFactoryInterface {

    /**
     * @param HttpResponseInterface $response
     */
    public function emitResponse(HttpResponseInterface $response) : void {
        $messageAdapter                         = new HttpMessageAdapter();
        $messageAdapter->emitResponse($response);
    }

    /**
     * @param int $code
     * @param string $reasonPhrase
     * @return HttpResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = '') : HttpResponseInterface {
        return new HttpResponse(
            $code,
            [],
            null,
            "1.1",
            $reasonPhrase
        );
    }

    /**
     * @param int $responseCode
     * @param $content
     * @return HttpResponseInterface
     */
    public function createJsonResponse(int $responseCode, $content) : HttpResponseInterface {
        if (is_array($content)) {
            $content                                = json_encode($content);
        } elseif (is_object($content)) {
            if ($content instanceof JsonSerializable) {
                $content                            = json_encode($content->jsonSerialize());
            } else {
                throw new UnexpectedValueException("createJsonResponse content has to be an instance of JsonSerializable");
            }
        }
        else {
            throw new UnexpectedValueException("createJsonResponse expected (array,object) for content, given ".gettype($content));
        }
        //@codeCoverageIgnoreStart
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException("createJsonResponse unable to encode content: ".json_last_error_msg());
        }
        //@codeCoverageIgnoreEnd
        return new HttpResponse($responseCode, ["Content-Type" => "application/json"], $content);
    }
}