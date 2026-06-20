<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Faragoman v2 — Front Controller (single entry point)
|--------------------------------------------------------------------------
|
| Every request enters here. Bootstraps the autoloader, config and kernel,
| then converts the incoming Request into a Response.
*/

use App\Core\Application;
use App\Core\Request;
use App\Support\Autoloader;

$basePath = dirname(__DIR__);

// 1. Autoloading (no Composer required on the host).
require $basePath . '/app/Support/Autoloader.php';
(new Autoloader(['App\\' => $basePath . '/app']))->register();
require $basePath . '/app/Support/helpers.php';

// 2. Configuration.
$config = [
    'app'      => require $basePath . '/config/app.php',
    'database' => require $basePath . '/config/database.php',
];

// 3. Runtime hardening.
date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Tehran');
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// 4. Handle the request.
$app = new Application($basePath, $config);
$response = $app->handle(Request::capture());
$response->send();
