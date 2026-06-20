<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Support\Rbac;
use Closure;

/**
 * Permission gate. Configured per-route as `role:<permission.slug>` and backed
 * by the dynamic RBAC engine so the Super Administrator can change access
 * without touching code.
 */
final class RoleMiddleware implements MiddlewareInterface
{
    private string $permission = '';

    public function __construct(private AuthService $auth, private Rbac $rbac)
    {
    }

    public function require(string $permission): self
    {
        $clone = clone $this;
        $clone->permission = $permission;

        return $clone;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            return Response::redirect('/login');
        }

        if ($this->permission !== '' && !$this->rbac->can($user, $this->permission)) {
            return $request->isAjax()
                ? Response::json(['error' => 'Forbidden'], 403)
                : Response::html('<h1>۴۰۳ — دسترسی غیرمجاز</h1>', 403);
        }

        return $next($request);
    }
}
