<?php
namespace Api;

/**
 * Represents the HTTP response object that will be sent to the client.
 */
class Response {
    const OK = 200;
    const CREATED = 201;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    const INTERNAL_SERVER_ERROR = 500;

    /** @var integer */
    private $code;
    /** @var string */
    private $message;
    /** @var mixed[] */
    private $content;

    /**
     * Constructs a new instance of an HTTP response object.
     *
     * @param integer $code the HTTP status code of the response. Defaults to 200 (OK)
     * @param string $message the message to send back to the client.
     * @param mixed[]|null $content the content to send along with the response body.
     */
    public function __construct($code = self::OK, $message = '', $content = null) {
        $this->code = $code;
        $this->message = $message;
        $this->content = $content;
    }

    /**
     * Serializes the response into a JSON string.
     *
     * @return string
     */
    public function serialize() {
        return \json_encode(array(
            'code' => $this->code,
            'message' => $this->message,
            'content' => $this->content
        ));
    }

    /**
     * Set the HTTP response code
     *
     * @return  self
     */ 
    public function setCode($code) {
        $this->code = $code;

        return $this;
    }

    /**
     * Set the message for the HTTP body
     *
     * @return  self
     */ 
    public function setMessage($message) {
        $this->message = $message;

        return $this;
    }

    /**
     * Set the content for the HTTP body
     *
     * @return  self
     */ 
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the HTTP response code
     */ 
    public function getCode() {
        return $this->code;
    }

    /**
     * Get the message for the HTTP body
     */ 
    public function getMessage() {
        return $this->message;
    }

    /**
     * Get the content for the HTTP body
     */ 
    public function getContent() {
        return $this->content;
    }
}
