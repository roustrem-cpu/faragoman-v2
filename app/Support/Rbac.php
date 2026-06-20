<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;

/**
 * Hierarchical, fully dynamic Role-Based Access Control.
 *
 * Design goals:
 *  - Backward compatible: the legacy `users.role` string still works. Legacy
 *    values map onto the new role hierarchy automatically.
 *  - Extensible without code changes: the Super Administrator assigns any
 *    permission to any role/user via the `roles`, `permissions`,
 *    `role_permissions` and `user_permissions` tables.
 *  - The Super Administrator implicitly has every permission.
 *
 * Hierarchy (rank, higher = more powerful):
 *   super_admin(100) > section_admin(80) > editor(60) > author(40) > user(10)
 */
final class Rbac
{
    public const SUPER_ADMIN   = 'super_admin';
    public const SECTION_ADMIN = 'section_admin';
    public const EDITOR        = 'editor';
    public const AUTHOR        = 'author';
    public const USER          = 'user';

    /** @var array<string, int> */
    private const HIERARCHY = [
        self::SUPER_ADMIN   => 100,
        self::SECTION_ADMIN => 80,
        self::EDITOR        => 60,
        self::AUTHOR        => 40,
        self::USER          => 10,
    ];

    /**
     * Maps the legacy `role` strings onto the new role keys so existing
     * accounts keep working with no data migration.
     *
     * @var array<string, string>
     */
    private const LEGACY_MAP = [
        'admin'          => self::SUPER_ADMIN,
        'section_admin'  => self::SECTION_ADMIN,
        'editor'         => self::EDITOR,
        'author'         => self::AUTHOR,
        'author_pending' => self::USER,
        'user'           => self::USER,
        ''               => self::USER,
    ];

    public function __construct(private Database $db)
    {
    }

    public function normaliseRole(?string $legacyRole): string
    {
        $key = strtolower((string) $legacyRole);

        return self::LEGACY_MAP[$key] ?? (isset(self::HIERARCHY[$key]) ? $key : self::USER);
    }

    public function rank(?string $role): int
    {
        return self::HIERARCHY[$this->normaliseRole($role)] ?? 0;
    }

    public function isSuperAdmin(?string $role): bool
    {
        return $this->normaliseRole($role) === self::SUPER_ADMIN;
    }

    /**
     * Does the given user have a permission?
     *
     * Resolution order:
     *   1. Super Admins bypass all checks.
     *   2. Direct user overrides (grant/deny) in `user_permissions`.
     *   3. Permissions inherited from the user's role.
     *
     * Falls back to a sane hard-coded matrix when the RBAC tables are not yet
     * present, so the app never breaks during a partial deployment.
     *
     * @param array{id?:int,role?:string} $user
     */
    public function can(array $user, string $permission): bool
    {
        $role = $this->normaliseRole($user['role'] ?? self::USER);

        if ($role === self::SUPER_ADMIN) {
            return true;
        }

        try {
            $userId = (int) ($user['id'] ?? 0);

            if ($userId > 0) {
                $override = $this->db->selectOne(
                    'SELECT effect FROM user_permissions up
                     JOIN permissions p ON p.id = up.permission_id
                     WHERE up.user_id = ? AND p.slug = ? LIMIT 1',
                    [$userId, $permission],
                );
                if ($override !== null) {
                    return $override['effect'] === 'allow';
                }
            }

            $row = $this->db->selectOne(
                'SELECT 1 FROM role_permissions rp
                 JOIN roles r ON r.id = rp.role_id
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE r.slug = ? AND p.slug = ? LIMIT 1',
                [$role, $permission],
            );

            return $row !== null;
        } catch (\Throwable) {
            return $this->fallbackMatrix($role, $permission);
        }
    }

    /**
     * Conservative default matrix used only before the RBAC tables exist.
     */
    private function fallbackMatrix(string $role, string $permission): bool
    {
        return match ($role) {
            self::SECTION_ADMIN => !str_starts_with($permission, 'roles.'),
            self::EDITOR        => str_starts_with($permission, 'content.') || str_starts_with($permission, 'comments.'),
            self::AUTHOR        => in_array($permission, ['content.create', 'content.update_own', 'content.view'], true),
            default             => false,
        };
    }
}
