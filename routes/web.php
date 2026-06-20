<?php

declare(strict_types=1);

use App\Controllers\ArticleController;
use App\Controllers\AuthController;
use App\Controllers\FeedController;
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

    // Article detail page (resolved by title). MUST stay LAST: its single-segment
    // pattern `/{title}` is a catch-all, so every specific route above wins first.
    // This closes the 404 the home feed cards used to hit (they link to /{title}).
    $router->get('/{title}', [ArticleController::class, 'show']);

    // Further routes (category, author, search, profile, wiki, admin…) are added
    // incrementally in later phases of the rewrite.
};
