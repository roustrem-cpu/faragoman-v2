<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Throwable;

/**
 * Every read/write against the additive RBAC tables (`roles`, `permissions`,
 * `role_permissions`, `user_permissions`) plus the role-assignment column on
 * the existing `users` table lives here. The rest of the app never issues raw
 * RBAC SQL — that is the whole point of the Repository Pattern.
 *
 * Backward compatibility:
 *  - The RBAC tables are OPTIONAL (created with `IF NOT EXISTS`). When they are
 *    absent {@see self::tablesReady()} returns false and the controller renders
 *    a guided "import schema.sql" notice instead of fataling — mirroring the
 *    graceful fallback already built into {@see \App\Support\Rbac}.
 *  - Assigning a role only ever writes to the existing `users.role` string
 *    column (no schema change), so legacy modules keep reading it verbatim.
 */
final class RbacRepository
{
    public function __construct(private Database $db)
    {
    }

    /**
     * True once the additive RBAC tables have been imported. Used to degrade
     * gracefully on databases where database/schema.sql was not yet run.
     */
    public function tablesReady(): bool
    {
        try {
            $this->db->selectOne('SELECT 1 FROM roles LIMIT 1');
            $this->db->selectOne('SELECT 1 FROM permissions LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    // -- Roles --------------------------------------------------------------

    /** @return array<int, Role> */
    public function allRoles(): array
    {
        $rows = $this->db->select(
            'SELECT id, slug, name, `rank`, created_at FROM roles ORDER BY `rank` DESC, id ASC',
        );

        return array_map(static fn (array $r): Role => Role::fromRow($r), $rows);
    }

    public function findRole(int $id): ?Role
    {
        $row = $this->db->selectOne(
            'SELECT id, slug, name, `rank`, created_at FROM roles WHERE id = ? LIMIT 1',
            [$id],
        );

        return $row ? Role::fromRow($row) : null;
    }

    public function findRoleBySlug(string $slug): ?Role
    {
        $row = $this->db->selectOne(
            'SELECT id, slug, name, `rank`, created_at FROM roles WHERE slug = ? LIMIT 1',
            [$slug],
        );

        return $row ? Role::fromRow($row) : null;
    }

    public function slugTaken(string $slug, int $exceptId = 0): bool
    {
        $row = $this->db->selectOne(
            'SELECT id FROM roles WHERE slug = ? AND id <> ? LIMIT 1',
            [$slug, $exceptId],
        );

        return $row !== null;
    }

    public function createRole(string $slug, string $name, int $rank): int
    {
        $this->db->statement(
            'INSERT INTO roles (slug, name, `rank`, created_at) VALUES (?, ?, ?, NOW())',
            [$slug, $name, $rank],
        );

        return $this->db->lastInsertId();
    }

    public function updateRole(int $id, string $slug, string $name, int $rank): void
    {
        $this->db->statement(
            'UPDATE roles SET slug = ?, name = ?, `rank` = ? WHERE id = ?',
            [$slug, $name, $rank, $id],
        );
    }

    /**
     * Deletes a role. `role_permissions` rows are removed by the ON DELETE
     * CASCADE foreign key; we also defensively delete them for databases where
     * the FK was not created.
     */
    public function deleteRole(int $id): void
    {
        $this->db->statement('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
        $this->db->statement('DELETE FROM roles WHERE id = ?', [$id]);
    }

    /**
     * Permission count per role id, e.g. [2 => 9, 3 => 5].
     *
     * @return array<int, int>
     */
    public function permissionCounts(): array
    {
        $rows = $this->db->select(
            'SELECT role_id, COUNT(*) AS c FROM role_permissions GROUP BY role_id',
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['role_id']] = (int) $row['c'];
        }

        return $counts;
    }

    // -- Permissions --------------------------------------------------------

    /** @return array<int, Permission> */
    public function allPermissions(): array
    {
        $rows = $this->db->select(
            'SELECT id, slug, name, category FROM permissions ORDER BY category ASC, id ASC',
        );

        return array_map(static fn (array $r): Permission => Permission::fromRow($r), $rows);
    }

    /** @return array<int, int> the permission ids granted to a role */
    public function permissionIdsForRole(int $roleId): array
    {
        $rows = $this->db->select(
            'SELECT permission_id FROM role_permissions WHERE role_id = ?',
            [$roleId],
        );

        return array_map(static fn (array $r): int => (int) $r['permission_id'], $rows);
    }

    /**
     * Replace the full permission set for a role (additive deletes/inserts only
     * against the RBAC join table — never touches a legacy table).
     *
     * @param array<int, int> $permissionIds
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->statement('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

        foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
            if ($permissionId <= 0) {
                continue;
            }
            $this->db->statement(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                [$roleId, $permissionId],
            );
        }
    }

    // -- Users & role assignment -------------------------------------------

    /**
     * Paginated user list for the assignment screen.
     *
     * @return array<int, User>
     */
    public function users(int $limit, int $offset): array
    {
        $rows = $this->db->select(
            'SELECT id, username, email, role, display_name, avatar_url, is_banned, user_title, user_bio, created_at
             FROM users ORDER BY id DESC LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(static fn (array $r): User => User::fromRow($r), $rows);
    }

    public function countUsers(): int
    {
        $row = $this->db->selectOne('SELECT COUNT(*) AS c FROM users');

        return (int) ($row['c'] ?? 0);
    }

    public function findUser(int $id): ?User
    {
        $row = $this->db->selectOne(
            'SELECT id, username, email, role, display_name, avatar_url, is_banned, user_title, user_bio, created_at
             FROM users WHERE id = ? LIMIT 1',
            [$id],
        );

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Assign a role to a user by writing the role slug to the existing
     * `users.role` column. Backward compatible: no schema change, the column
     * already exists and legacy code keeps reading it.
     */
    public function setUserRole(int $userId, string $roleSlug): void
    {
        $this->db->statement('UPDATE users SET role = ? WHERE id = ?', [$roleSlug, $userId]);
    }

    // -- Per-user permission overrides -------------------------------------

    /**
     * Current overrides for a user keyed by permission id, e.g.
     * [4 => 'allow', 7 => 'deny'].
     *
     * @return array<int, string>
     */
    public function userOverrides(int $userId): array
    {
        $rows = $this->db->select(
            'SELECT permission_id, effect FROM user_permissions WHERE user_id = ?',
            [$userId],
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['permission_id']] = (string) $row['effect'];
        }

        return $map;
    }

    /**
     * Replace the full override set for a user. Permissions mapped to
     * 'inherit' (or anything other than allow/deny) are removed so the user
     * falls back to their role's grants.
     *
     * @param array<int, string> $effects permission id => allow|deny|inherit
     */
    public function syncUserOverrides(int $userId, array $effects): void
    {
        $this->db->statement('DELETE FROM user_permissions WHERE user_id = ?', [$userId]);

        foreach ($effects as $permissionId => $effect) {
            $permissionId = (int) $permissionId;
            if ($permissionId <= 0 || !in_array($effect, ['allow', 'deny'], true)) {
                continue;
            }
            $this->db->statement(
                'INSERT INTO user_permissions (user_id, permission_id, effect) VALUES (?, ?, ?)',
                [$userId, $permissionId, $effect],
            );
        }
    }
}
