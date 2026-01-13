<?php

namespace SellNow\Core;

use Closure;
use Exception;

/**
 * HTTP Router
 * 
 * Handles route registration and dispatching with middleware support.
 * Supports HTTP methods (GET, POST, PUT, DELETE) and route parameters.
 * 
 * @package SellNow\Core
 */
class Router
{
    /**
     * Registered routes
     * @var array
     */
    private array $routes = [];

    /**
     * Global middleware applied to all routes
     * @var array
     */
    private array $globalMiddleware = [];

    /**
     * Dependency injection container
     * @var Container
     */
    private Container $container;

    /**
     * Constructor
     * 
     * @param Container $container Dependency injection container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a GET route
     * 
     * @param string $uri Route URI pattern
     * @param callable|array $action Controller action
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function get(string $uri, $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Register a POST route
     * 
     * @param string $uri Route URI pattern
     * @param callable|array $action Controller action
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function post(string $uri, $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Register a PUT route
     * 
     * @param string $uri Route URI pattern
     * @param callable|array $action Controller action
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function put(string $uri, $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $uri Route URI pattern
     * @param callable|array $action Controller action
     * @param array $middleware Route-specific middleware
     * @return void
     */
    public function delete(string $uri, $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Add a route to the collection
     * 
     * @param string $method HTTP method
     * @param string $uri Route URI pattern
     * @param callable|array $action Controller action
     * @param array $middleware Route-specific middleware
     * @return void
     */
    private function addRoute(string $method, string $uri, $action, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware
        ];
    }

    /**
     * Add global middleware
     * 
     * @param string $middleware Middleware class name
     * @return void
     */
    public function addGlobalMiddleware(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Dispatch the request to the appropriate route
     * 
     * @param Request $request HTTP request
     * @return Response HTTP response
     * @throws Exception If route not found
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['uri'], $uri);
            
            if ($params !== false) {
                // Set route parameters in request
                $request->setParams($params);

                // Build middleware stack
                $middleware = array_merge($this->globalMiddleware, $route['middleware']);

                // Execute middleware and action
                return $this->runMiddleware($middleware, $request, function ($request) use ($route) {
                    return $this->callAction($route['action'], $request);
                });
            }
        }

        // No route found
        return Response::notFound('404 - Page Not Found');
    }

    /**
     * Match a route pattern against a URI
     * 
     * @param string $pattern Route pattern
     * @param string $uri Request URI
     * @return array|false Route parameters or false if no match
     */
    private function matchRoute(string $pattern, string $uri)
    {
        // Exact match
        if ($pattern === $uri) {
            return [];
        }

        // Convert route pattern to regex
        // Support {param} and {param?} for optional parameters
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '(?:([^/]+))?', $pattern);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Remove full match
            array_shift($matches);
            
            // Extract parameter names from original pattern
            preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', func_get_arg(0), $paramNames);
            
            $params = [];
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
            
            return $params;
        }

        return false;
    }

    /**
     * Run middleware stack
     * 
     * @param array $middleware Middleware classes
     * @param Request $request HTTP request
     * @param Closure $next Next handler
     * @return Response HTTP response
     */
    private function runMiddleware(array $middleware, Request $request, Closure $next): Response
    {
        // If no middleware, just call the next handler
        if (empty($middleware)) {
            return $next($request);
        }

        // Get the first middleware
        $middlewareClass = array_shift($middleware);

        // Resolve middleware from container
        $middlewareInstance = $this->container->make($middlewareClass);

        // Call middleware with next handler
        return $middlewareInstance->handle($request, function ($request) use ($middleware, $next) {
            return $this->runMiddleware($middleware, $request, $next);
        });
    }

    /**
     * Call the route action
     * 
     * @param callable|array $action Controller action
     * @param Request $request HTTP request
     * @return Response HTTP response
     * @throws Exception If action is invalid
     */
    private function callAction($action, Request $request): Response
    {
        // If action is a closure
        if ($action instanceof Closure) {
            $result = $action($request);
            return $this->toResponse($result);
        }

        // If action is [ControllerClass, 'method']
        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;

            // Resolve controller from container
            $controller = $this->container->make($controllerClass);

            // Call controller method
            $result = $controller->$method($request);
            return $this->toResponse($result);
        }

        throw new Exception('Invalid route action');
    }

    /**
     * Convert action result to Response
     * 
     * @param mixed $result Action result
     * @return Response HTTP response
     */
    private function toResponse($result): Response
    {
        // If already a Response, return it
        if ($result instanceof Response) {
            return $result;
        }

        // If string, wrap in Response
        if (is_string($result)) {
            return Response::make($result);
        }

        // If array, return as JSON
        if (is_array($result)) {
            return Response::json($result);
        }

        // Default: convert to string
        return Response::make((string) $result);
    }
}
