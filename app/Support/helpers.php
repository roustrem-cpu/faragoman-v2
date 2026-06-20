<?php

declare(strict_types=1);

/**
 * Global view helpers. Kept tiny and pure; included once at bootstrap.
 */

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    /** Local, versioned asset URL (cache-busted by file mtime). */
    function asset(string $path): string
    {
        $path = '/assets/' . ltrim($path, '/');
        $absolute = dirname(__DIR__, 2) . '/public' . $path;
        $version = is_file($absolute) ? (string) filemtime($absolute) : '1';

        return $path . '?v=' . $version;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return (string) ($_SESSION['_csrf'] ?? '');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        return e($_POST[$key] ?? $default);
    }
}
