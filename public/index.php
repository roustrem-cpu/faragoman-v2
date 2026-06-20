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
use App\Core\Database;
use App\Core\Request;
use App\Support\Autoloader;
use App\Support\LegacyBridge;

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

// 4. Bootstrap the application kernel.
$app = new Application($basePath, $config);

// 4b. Legacy module mount point (Store & Chat) — served untouched.
//     The original modules expect a global mysqli `$conn`; LegacyBridge exposes
//     the SAME connection managed by the new Database layer, so the legacy code
//     runs verbatim with no edits and a single source of DB credentials.
//     Drop the untouched modules in:  <basePath>/legacy/store  and  <basePath>/legacy/chat
//     This block is a safe no-op until those files are present.
$request = Request::capture();
$path = $request->path();

if (LegacyBridge::isLegacyPath($path)) {
    $module = str_starts_with(ltrim($path, '/'), 'chat') ? 'chat' : 'store';
    $legacyEntry = $basePath . '/legacy/' . $module . '/index.php';

    if (is_file($legacyEntry)) {
        LegacyBridge::boot($app->container()->get(Database::class));
        $conn = $GLOBALS['conn']; // legacy scripts reference $conn in local scope
        require $legacyEntry;
        exit;
    }
}

// 5. Handle the request through the router.
$response = $app->handle($request);
$response->send();
