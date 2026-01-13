<?php

namespace SellNow\Core;

/**
 * HTTP Request Abstraction
 * 
 * Provides a clean interface to access HTTP request data with built-in
 * sanitization and validation capabilities.
 * 
 * @package SellNow\Core
 */
class Request
{
    /**
     * GET parameters
     * @var array
     */
    private array $query;

    /**
     * POST parameters
     * @var array
     */
    private array $post;

    /**
     * Uploaded files
     * @var array
     */
    private array $files;

    /**
     * Request headers
     * @var array
     */
    private array $headers;

    /**
     * Request URI
     * @var string
     */
    private string $uri;

    /**
     * HTTP method
     * @var string
     */
    private string $method;

    /**
     * Route parameters (set by router)
     * @var array
     */
    private array $params = [];

    /**
     * Create a new Request instance from globals
     * 
     * @return static
     */
    public static function createFromGlobals(): static
    {
        return new static(
            $_GET,
            $_POST,
            $_FILES,
            getallheaders() ?: [],
            $_SERVER['REQUEST_URI'] ?? '/',
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    /**
     * Constructor
     * 
     * @param array $query GET parameters
     * @param array $post POST parameters
     * @param array $files Uploaded files
     * @param array $headers Request headers
     * @param string $uri Request URI
     * @param string $method HTTP method
     */
    public function __construct(
        array $query = [],
        array $post = [],
        array $files = [],
        array $headers = [],
        string $uri = '/',
        string $method = 'GET'
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->headers = $headers;
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->method = strtoupper($method);
    }

    /**
     * Get a value from the request (checks POST, then GET)
     * 
     * @param string $key The parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get a value from POST data
     * 
     * @param string $key The parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a value from GET data
     * 
     * @param string $key The parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all POST data
     * 
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get only specified keys from request
     * 
     * @param array $keys Keys to retrieve
     * @return array
     */
    public function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Get all except specified keys from request
     * 
     * @param array $keys Keys to exclude
     * @return array
     */
    public function except(array $keys): array
    {
        $data = $this->all();
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Check if request has a specific key
     * 
     * @param string $key The parameter name
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    /**
     * Get an uploaded file
     * 
     * @param string $key The file input name
     * @return array|null File data or null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if request has a file
     * 
     * @param string $key The file input name
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get a header value
     * 
     * @param string $key Header name
     * @param mixed $default Default value
     * @return mixed
     */
    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get the request URI
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the HTTP method
     * 
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Check if request is GET
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    /**
     * Check if request is POST
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * Set route parameters (called by router)
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get a route parameter
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get all route parameters
     * 
     * @return array
     */
    public function params(): array
    {
        return $this->params;
    }
}
