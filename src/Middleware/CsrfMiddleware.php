<?php

namespace SellNow\Middleware;

use Closure;
use SellNow\Core\Request;
use SellNow\Core\Response;
use SellNow\Security\CsrfToken;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE).
 * Prevents Cross-Site Request Forgery attacks.
 * 
 * @package SellNow\Middleware
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * CSRF token manager
     * @var CsrfToken
     */
    private CsrfToken $csrfToken;

    /**
     * Constructor
     * 
     * @param CsrfToken $csrfToken CSRF token manager
     */
    public function __construct(CsrfToken $csrfToken)
    {
        $this->csrfToken = $csrfToken;
    }

    /**
     * Handle an incoming request
     * 
     * @param Request $request HTTP request
     * @param Closure $next Next middleware/handler
     * @return Response HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate CSRF on state-changing methods
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $token = $request->input('_csrf_token');

            if (!$this->csrfToken->validate($token)) {
                return Response::make('CSRF token validation failed', 403);
            }
        }

        return $next($request);
    }
}
