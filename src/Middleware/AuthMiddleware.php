<?php

namespace SellNow\Middleware;

use Closure;
use SellNow\Core\Request;
use SellNow\Core\Response;

/**
 * Authentication Middleware
 * 
 * Ensures user is authenticated before accessing protected routes.
 * Redirects to login page if not authenticated.
 * 
 * @package SellNow\Middleware
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle an incoming request
     * 
     * @param Request $request HTTP request
     * @param Closure $next Next middleware/handler
     * @return Response HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $request->getUri();
            
            return Response::redirect('/login');
        }

        // User is authenticated, continue
        return $next($request);
    }
}
