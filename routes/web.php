<?php

declare(strict_types=1);

use App\Controllers\AdminArticleController;
use App\Controllers\AdminCommentController;
use App\Controllers\AdminController;
use App\Controllers\AdminRoleController;
use App\Controllers\AdminUserController;
use App\Controllers\ArticleController;
use App\Controllers\AuthController;
use App\Controllers\AuthorController;
use App\Controllers\CategoryController;
use App\Controllers\FeedController;
use App\Controllers\SearchController;
use App\Controllers\HomeController;
use App\Controllers\StoryController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Routing is declared here, fully separated from controllers. Each route may
| attach a middleware pipeline by class name (resolved from the container).
|
| NOTE: The legacy Store (/store) and Chat (/chat) modules are mounted as-is
| by public/index.php BEFORE the router runs, so their behaviour is untouched.
*/

return static function (Router $router): void {
    // Public
    $router->get('/', [HomeController::class, 'index']);

    // Authentication
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Stories (re-enabled feature) ---------------------------------------
    $router->get('/stories', [StoryController::class, 'index']);
    $router->post('/stories', [StoryController::class, 'store'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $router->post('/stories/{id}/delete', [StoryController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);

    // Syndication: RSS, JSON Feed and a CLI/terminal-friendly plain-text feed.
    $router->get('/feed', [FeedController::class, 'index']);
    $router->get('/feed/rss', [FeedController::class, 'rss']);
    $router->get('/feed.json', [FeedController::class, 'json']);
    $router->get('/feed.txt', [FeedController::class, 'text']);

    // Admin panel (Phase 3 foundation). Gated by auth + the dynamic RBAC
    // permission `admin.access`, resolved via the `gate.admin` middleware.
    $router->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, 'gate.admin']);

    // Admin: article management (Task D). Reads gated by auth + admin.access;
    // writes additionally pass through CSRF protection.
    $router->get('/admin/articles', [AdminArticleController::class, 'index'], [AuthMiddleware::class, 'gate.admin']);
    $router->get('/admin/articles/create', [AdminArticleController::class, 'create'], [AuthMiddleware::class, 'gate.admin']);
    $router->post('/admin/articles', [AdminArticleController::class, 'store'], [AuthMiddleware::class, 'gate.admin', CsrfMiddleware::class]);
    $router->get('/admin/articles/{id}/edit', [AdminArticleController::class, 'edit'], [AuthMiddleware::class, 'gate.admin']);
    $router->post('/admin/articles/{id}', [AdminArticleController::class, 'update'], [AuthMiddleware::class, 'gate.admin', CsrfMiddleware::class]);
    $router->post('/admin/articles/{id}/delete', [AdminArticleController::class, 'destroy'], [AuthMiddleware::class, 'gate.admin', CsrfMiddleware::class]);
    $router->post('/admin/articles/{id}/publish', [AdminArticleController::class, 'publish'], [AuthMiddleware::class, 'gate.admin', CsrfMiddleware::class]);
    $router->post('/admin/articles/{id}/unpublish', [AdminArticleController::class, 'unpublish'], [AuthMiddleware::class, 'gate.admin', CsrfMiddleware::class]);

    // Admin: dynamic RBAC management (Task E). Roles, role permissions, role
    // assignment and per-user overrides. Gated by auth + the `roles.manage`
    // permission via the `gate.roles` middleware; writes additionally pass
    // through CSRF. Static segments are registered before the `{id}` patterns.
    $router->get('/admin/roles', [AdminRoleController::class, 'index'], [AuthMiddleware::class, 'gate.roles']);
    $router->get('/admin/roles/create', [AdminRoleController::class, 'create'], [AuthMiddleware::class, 'gate.roles']);
    $router->post('/admin/roles', [AdminRoleController::class, 'store'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);
    $router->get('/admin/roles/users', [AdminRoleController::class, 'users'], [AuthMiddleware::class, 'gate.roles']);
    $router->post('/admin/roles/users/{id}/role', [AdminRoleController::class, 'assignRole'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);
    $router->get('/admin/roles/users/{id}/overrides', [AdminRoleController::class, 'overrides'], [AuthMiddleware::class, 'gate.roles']);
    $router->post('/admin/roles/users/{id}/overrides', [AdminRoleController::class, 'saveOverrides'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);
    $router->get('/admin/roles/{id}/edit', [AdminRoleController::class, 'edit'], [AuthMiddleware::class, 'gate.roles']);
    $router->get('/admin/roles/{id}/permissions', [AdminRoleController::class, 'permissions'], [AuthMiddleware::class, 'gate.roles']);
    $router->post('/admin/roles/{id}/permissions', [AdminRoleController::class, 'savePermissions'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);
    $router->post('/admin/roles/{id}', [AdminRoleController::class, 'update'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);
    $router->post('/admin/roles/{id}/delete', [AdminRoleController::class, 'destroy'], [AuthMiddleware::class, 'gate.roles', CsrfMiddleware::class]);

    // Admin: user management (Task F). List/search, edit profile, ban/unban
    // under /admin/users. Gated by auth + the `users.manage` permission via the
    // `gate.users` middleware; writes additionally pass through CSRF. Static
    // segments registered before the `{id}` patterns.
    $router->get('/admin/users', [AdminUserController::class, 'index'], [AuthMiddleware::class, 'gate.users']);
    $router->get('/admin/users/{id}/edit', [AdminUserController::class, 'edit'], [AuthMiddleware::class, 'gate.users']);
    $router->post('/admin/users/{id}', [AdminUserController::class, 'update'], [AuthMiddleware::class, 'gate.users', CsrfMiddleware::class]);
    $router->post('/admin/users/{id}/ban', [AdminUserController::class, 'ban'], [AuthMiddleware::class, 'gate.users', CsrfMiddleware::class]);
    $router->post('/admin/users/{id}/unban', [AdminUserController::class, 'unban'], [AuthMiddleware::class, 'gate.users', CsrfMiddleware::class]);

    // Admin: comment moderation (Task G). List/approve/reject/delete comments
    // under /admin/comments. Gated by auth + the `comments.moderate` permission
    // via the `gate.comments` middleware; writes additionally pass through CSRF.
    // Static segment registered before the `{id}` action patterns, and the whole
    // block stays before the `/{title}` catch-all.
    $router->get('/admin/comments', [AdminCommentController::class, 'index'], [AuthMiddleware::class, 'gate.comments']);
    $router->post('/admin/comments/{id}/approve', [AdminCommentController::class, 'approve'], [AuthMiddleware::class, 'gate.comments', CsrfMiddleware::class]);
    $router->post('/admin/comments/{id}/reject', [AdminCommentController::class, 'reject'], [AuthMiddleware::class, 'gate.comments', CsrfMiddleware::class]);
    $router->post('/admin/comments/{id}/delete', [AdminCommentController::class, 'destroy'], [AuthMiddleware::class, 'gate.comments', CsrfMiddleware::class]);

    // Content discovery (Phase 3): search, category and author listings.
    // Registered BEFORE the /{title} catch-all so they always win.
    $router->get('/search', [SearchController::class, 'index']);
    $router->get('/category/{id}', [CategoryController::class, 'show']);
    $router->get('/author/{id}', [AuthorController::class, 'show']);

    // Article detail page (resolved by title). MUST stay LAST: its single-segment
    // pattern `/{title}` is a catch-all, so every specific route above wins first.
    // This closes the 404 the home feed cards used to hit (they link to /{title}).
    $router->get('/{title}', [ArticleController::class, 'show']);

    // Further routes (category, author, search, profile, wiki, admin…) are added
    // incrementally in later phases of the rewrite.
};
