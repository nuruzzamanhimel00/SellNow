<?php

namespace SellNow\Middleware;

use Closure;
use SellNow\Core\Request;
use SellNow\Core\Response;

/**
 * Middleware Interface
 * 
 * All middleware must implement this interface.
 * Middleware can inspect/modify requests and responses.
 * 
 * @package SellNow\Middleware
 */
interface MiddlewareInterface
{
    /**
     * Handle an incoming request
     * 
     * @param Request $request HTTP request
     * @param Closure $next Next middleware/handler
     * @return Response HTTP response
     */
    public function handle(Request $request, Closure $next): Response;
}
