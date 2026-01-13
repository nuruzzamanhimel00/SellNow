<?php

namespace SellNow\Core;

/**
 * HTTP Response Abstraction
 * 
 * Provides a clean interface to build and send HTTP responses with
 * proper status codes, headers, and content types.
 * 
 * @package SellNow\Core
 */
class Response
{
    /**
     * Response content
     * @var string
     */
    private string $content;

    /**
     * HTTP status code
     * @var int
     */
    private int $statusCode;

    /**
     * Response headers
     * @var array
     */
    private array $headers = [];

    /**
     * Constructor
     * 
     * @param string $content Response content
     * @param int $statusCode HTTP status code
     * @param array $headers Response headers
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Create a new Response instance
     * 
     * @param string $content Response content
     * @param int $statusCode HTTP status code
     * @param array $headers Response headers
     * @return static
     */
    public static function make(string $content = '', int $statusCode = 200, array $headers = []): static
    {
        return new static($content, $statusCode, $headers);
    }

    /**
     * Create a JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @param array $headers Additional headers
     * @return static
     */
    public static function json($data, int $statusCode = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data), $statusCode, $headers);
    }

    /**
     * Create a redirect response
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (302 by default)
     * @return static
     */
    public static function redirect(string $url, int $statusCode = 302): static
    {
        return new static('', $statusCode, ['Location' => $url]);
    }

    /**
     * Create a 404 Not Found response
     * 
     * @param string $message Error message
     * @return static
     */
    public static function notFound(string $message = '404 Not Found'): static
    {
        return new static($message, 404);
    }

    /**
     * Create a 403 Forbidden response
     * 
     * @param string $message Error message
     * @return static
     */
    public static function forbidden(string $message = '403 Forbidden'): static
    {
        return new static($message, 403);
    }

    /**
     * Create a 500 Internal Server Error response
     * 
     * @param string $message Error message
     * @return static
     */
    public static function error(string $message = '500 Internal Server Error'): static
    {
        return new static($message, 500);
    }

    /**
     * Set response content
     * 
     * @param string $content Response content
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get response content
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a header
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return $this
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Send the response to the client
     * 
     * @return void
     */
    public function send(): void
    {
        // Set HTTP status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
    }

    /**
     * Convert response to string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->content;
    }
}
