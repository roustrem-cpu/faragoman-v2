<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;

/**
 * Backward-compatibility bridge for the untouched legacy modules (Store & Chat).
 *
 * The legacy codebase expects a global mysqli handle named `$conn` (created by
 * the old `db_config.php`). Rather than duplicating credentials, this bridge
 * exposes the SAME connection managed by the new {@see Database} layer as the
 * global `$conn`, so legacy `require`-d scripts keep working verbatim with no
 * edits and a single source of truth for credentials (config/database.php).
 *
 * Usage (from public/index.php, before mounting a legacy module):
 *     LegacyBridge::boot($container->get(Database::class));
 */
final class LegacyBridge
{
    private static bool $booted = false;

    public static function boot(Database $database): void
    {
        if (self::$booted) {
            return;
        }

        // Expose the live mysqli handle under the legacy global name.
        $GLOBALS['conn'] = $database->connection();
        self::$booted = true;
    }

    /**
     * True when the current request path targets a legacy module that must be
     * served by the original code untouched.
     */
    public static function isLegacyPath(string $path): bool
    {
        $path = '/' . ltrim($path, '/');

        foreach (['/store', '/chat'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
