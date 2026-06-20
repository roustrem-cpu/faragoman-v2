<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\ArticleRepository;
use App\Repositories\UserRepository;
use App\Services\ArticleService;
use App\Services\AuthService;
use App\Support\Cache;
use App\Support\Rbac;
use Throwable;

/**
 * The application kernel: wires the service container, registers bindings
 * (composition root), loads routes and turns a Request into a Response.
 */
final class Application
{
    private Container $container;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private string $basePath, private array $config)
    {
        $this->container = new Container();
        $this->registerCoreServices();
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function registerCoreServices(): void
    {
        $c = $this->container;
        $config = $this->config;
        $basePath = $this->basePath;

        $c->instance('config', $config);

        $c->singleton(Database::class, static fn (): Database => new Database($config['database']));
        $c->singleton(Cache::class, static fn (): Cache => new Cache($basePath . '/storage/cache'));
        $c->singleton(View::class, static fn (): View => new View($basePath . '/resources/views'));
        $c->singleton(Rbac::class, static fn (Container $c): Rbac => new Rbac($c->get(Database::class)));

        // Repositories
        $c->singleton(UserRepository::class, static fn (Container $c): UserRepository => new UserRepository($c->get(Database::class)));
        $c->singleton(ArticleRepository::class, static fn (Container $c): ArticleRepository => new ArticleRepository($c->get(Database::class)));
        $c->singleton(\App\Repositories\StoryRepository::class, static fn (Container $c): \App\Repositories\StoryRepository => new \App\Repositories\StoryRepository($c->get(Database::class)));
        $c->singleton(\App\Repositories\RbacRepository::class, static fn (Container $c): \App\Repositories\RbacRepository => new \App\Repositories\RbacRepository($c->get(Database::class)));
        $c->singleton(\App\Repositories\CommentRepository::class, static fn (Container $c): \App\Repositories\CommentRepository => new \App\Repositories\CommentRepository($c->get(Database::class)));

        // Services
        $c->singleton(AuthService::class, static fn (Container $c): AuthService => new AuthService($c->get(UserRepository::class)));
        $c->singleton(ArticleService::class, static fn (Container $c): ArticleService => new ArticleService($c->get(ArticleRepository::class), $c->get(Cache::class)));
        $c->singleton(\App\Services\StoryService::class, static fn (Container $c): \App\Services\StoryService => new \App\Services\StoryService($c->get(\App\Repositories\StoryRepository::class), $c->get(Cache::class)));
        $c->singleton(\App\Services\RbacService::class, static fn (Container $c): \App\Services\RbacService => new \App\Services\RbacService($c->get(\App\Repositories\RbacRepository::class)));
        $c->singleton(\App\Services\UserService::class, static fn (Container $c): \App\Services\UserService => new \App\Services\UserService($c->get(UserRepository::class)));
        $c->singleton(\App\Services\CommentService::class, static fn (Container $c): \App\Services\CommentService => new \App\Services\CommentService($c->get(\App\Repositories\CommentRepository::class)));

        // Middleware
        $c->singleton(AuthMiddleware::class, static fn (Container $c): AuthMiddleware => new AuthMiddleware($c->get(AuthService::class)));
        $c->singleton(RoleMiddleware::class, static fn (Container $c): RoleMiddleware => new RoleMiddleware($c->get(AuthService::class), $c->get(Rbac::class)));
        $c->singleton(CsrfMiddleware::class, static fn (): CsrfMiddleware => new CsrfMiddleware());

        // Configured RBAC gate for the admin panel. The Router resolves route
        // middleware by container id, so this exposes a ready-to-use instance
        // of RoleMiddleware pre-bound to the `admin.access` permission.
        $c->singleton('gate.admin', static fn (Container $c): RoleMiddleware => $c->get(RoleMiddleware::class)->require('admin.access'));

        // Dedicated gate for the RBAC management section (Task E). More
        // sensitive than the rest of the panel, so it requires `roles.manage`
        // rather than the broad `admin.access`. By default only the Super
        // Admin (who bypasses all checks) holds it.
        $c->singleton('gate.roles', static fn (Container $c): RoleMiddleware => $c->get(RoleMiddleware::class)->require('roles.manage'));

        // Gate for the user-management section (Task F): the `users.manage`
        // permission (held by super_admin and section_admin in the seed).
        $c->singleton('gate.users', static fn (Container $c): RoleMiddleware => $c->get(RoleMiddleware::class)->require('users.manage'));

        // Gate for the comment-moderation section (Task G): the
        // `comments.moderate` permission. Per the seed this is held by
        // super_admin (bypass), section_admin and editor — so editors can
        // moderate discussion without holding the broader admin permissions.
        $c->singleton('gate.comments', static fn (Container $c): RoleMiddleware => $c->get(RoleMiddleware::class)->require('comments.moderate'));

        // Router
        $c->singleton(Router::class, static fn (Container $c): Router => new Router($c));

        // Controllers are resolved on demand by the router; register them lazily.
        foreach ($this->controllerBindings() as $class => $factory) {
            $c->singleton($class, $factory);
        }
    }

    /**
     * @return array<class-string, \Closure>
     */
    private function controllerBindings(): array
    {
        return [
            \App\Controllers\HomeController::class => static fn (Container $c) => new \App\Controllers\HomeController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class), $c->get(\App\Services\StoryService::class)),
            \App\Controllers\ArticleController::class => static fn (Container $c) => new \App\Controllers\ArticleController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\AdminController::class => static fn (Container $c) => new \App\Controllers\AdminController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\AdminArticleController::class => static fn (Container $c) => new \App\Controllers\AdminArticleController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\AdminRoleController::class => static fn (Container $c) => new \App\Controllers\AdminRoleController($c->get(View::class), $c->get(\App\Services\RbacService::class), $c->get(AuthService::class), $c->get(Rbac::class)),
            \App\Controllers\AdminUserController::class => static fn (Container $c) => new \App\Controllers\AdminUserController($c->get(View::class), $c->get(\App\Services\UserService::class), $c->get(AuthService::class), $c->get(Rbac::class)),
            \App\Controllers\AdminCommentController::class => static fn (Container $c) => new \App\Controllers\AdminCommentController($c->get(View::class), $c->get(\App\Services\CommentService::class), $c->get(AuthService::class)),
            \App\Controllers\CategoryController::class => static fn (Container $c) => new \App\Controllers\CategoryController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\AuthorController::class => static fn (Container $c) => new \App\Controllers\AuthorController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\SearchController::class => static fn (Container $c) => new \App\Controllers\SearchController($c->get(View::class), $c->get(ArticleService::class), $c->get(AuthService::class)),
            \App\Controllers\AuthController::class => static fn (Container $c) => new \App\Controllers\AuthController($c->get(View::class), $c->get(AuthService::class)),
            \App\Controllers\StoryController::class => static fn (Container $c) => new \App\Controllers\StoryController($c->get(\App\Services\StoryService::class), $c->get(AuthService::class), $c->get(Rbac::class)),
            \App\Controllers\FeedController::class => static fn (Container $c) => new \App\Controllers\FeedController($c->get(ArticleService::class)),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            /** @var Router $router */
            $router = $this->container->get(Router::class);
            $routes = require $this->basePath . '/routes/web.php';
            $routes($router);

            return $router->dispatch($request);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(Throwable $e): Response
    {
        $debug = (bool) ($this->config['app']['debug'] ?? false);
        error_log('[faragoman] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

        /** @var View $view */
        $view = $this->container->get(View::class);
        $body = $view->render('errors.500', [
            'message' => $debug ? $e->getMessage() : null,
            'trace'   => $debug ? $e->getTraceAsString() : null,
        ]);

        return Response::html($body, 500);
    }
}
