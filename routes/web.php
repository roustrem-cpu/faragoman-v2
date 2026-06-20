<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
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

    // Further routes (article, profile, wiki, admin, stories…) are added
    // incrementally in later phases of the rewrite.
};
