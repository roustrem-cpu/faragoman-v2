<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Stateless double-submit CSRF protection for state-changing requests.
 * The token lives in the session and is echoed into every form via csrf_field().
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = (string) $request->input('_csrf', '');

            if (!hash_equals((string) $_SESSION['_csrf'], $token)) {
                return Response::html('<h1>۴۱۹ — نشست منقضی شده است</h1>', 419);
            }
        }

        return $next($request);
    }
}
