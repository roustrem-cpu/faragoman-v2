<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use Closure;

/**
 * Guards routes that require an authenticated user. Redirects guests to the
 * login page (or returns 401 JSON for AJAX requests).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->auth->check()) {
            return $request->isAjax()
                ? Response::json(['error' => 'Unauthenticated'], 401)
                : Response::redirect('/login');
        }

        return $next($request);
    }
}
