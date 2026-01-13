<?php

namespace SellNow\Middleware;

use Closure;
use SellNow\Core\Request;
use SellNow\Core\Response;

/**
 * Rate Limiting Middleware
 * 
 * Prevents abuse by limiting the number of requests from a single IP.
 * Uses session-based tracking for simplicity.
 * 
 * @package SellNow\Middleware
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Maximum requests per window
     * @var int
     */
    private int $maxRequests;

    /**
     * Time window in seconds
     * @var int
     */
    private int $windowSeconds;

    /**
     * Constructor
     * 
     * @param int $maxRequests Maximum requests per window (default: 60)
     * @param int $windowSeconds Time window in seconds (default: 60)
     */
    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
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
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . md5($ip);

        // Initialize rate limit data if not exists
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $this->windowSeconds
            ];
        }

        $data = $_SESSION[$key];

        // Reset if window has passed
        if (time() > $data['reset_at']) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_at' => time() + $this->windowSeconds
            ];
            $data = $_SESSION[$key];
        }

        // Check if limit exceeded
        if ($data['count'] >= $this->maxRequests) {
            $retryAfter = $data['reset_at'] - time();
            return Response::make('Too Many Requests. Try again in ' . $retryAfter . ' seconds.', 429)
                ->setHeader('Retry-After', (string) $retryAfter);
        }

        // Increment counter
        $_SESSION[$key]['count']++;

        return $next($request);
    }
}
