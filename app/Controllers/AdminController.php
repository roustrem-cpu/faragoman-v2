<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Admin panel foundation.
 *
 * Entry point for the back office. Access is gated upstream by the route
 * middleware pipeline (AuthMiddleware + the `gate.admin` RoleMiddleware
 * configured for the `admin.access` permission), so the controller itself can
 * stay focused on presenting data through the dedicated admin layout.
 *
 * Read-only: no schema or data mutations.
 */
final class AdminController extends Controller
{
    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function dashboard(Request $request): Response
    {
        return $this->renderWith('layouts/admin', 'admin/dashboard', [
            'title'          => 'داشبورد مدیریت — فراگمان',
            'heading'        => 'داشبورد مدیریت',
            'activeNav'      => 'dashboard',
            'currentUser'    => $this->auth->userModel(),
            'publishedCount' => $this->articles->publishedCount(),
        ]);
    }
}
