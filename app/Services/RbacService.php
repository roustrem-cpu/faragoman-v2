<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RbacRepository;
use App\Support\Rbac;

/**
 * Business logic for the Dynamic RBAC Management UI (Task E).
 *
 * Sits between the AdminRoleController and the RbacRepository: validates input,
 * protects the core role hierarchy that {@see Rbac} depends on, and shapes data
 * for the views. Controllers stay thin; raw SQL stays in the repository.
 *
 * No caching: RBAC reads are light and {@see Rbac::can()} reads the tables
 * directly, so changes made here take effect on the very next request. (We also
 * avoid caching model instances because the shared file Cache unserializes with
 * allowed_classes => false — see the Task B note in HANDOFF.md.)
 */
final class RbacService
{
    /**
     * Seeded roles the new Rbac hierarchy relies on. They may be renamed /
     * re-ranked and have their permissions edited, but their slug is locked and
     * they cannot be deleted (prevents locking the Super Admin out or orphaning
     * the legacy-role mapping).
     *
     * @var array<int, string>
     */
    private const CORE_ROLES = [
        Rbac::SUPER_ADMIN,
        Rbac::SECTION_ADMIN,
        Rbac::EDITOR,
        Rbac::AUTHOR,
        Rbac::USER,
    ];

    public function __construct(private RbacRepository $repo)
    {
    }

    public function ready(): bool
    {
        return $this->repo->tablesReady();
    }

    public function isCoreRole(string $slug): bool
    {
        return in_array($slug, self::CORE_ROLES, true);
    }

    public function isSuperAdminRole(string $slug): bool
    {
        return $slug === Rbac::SUPER_ADMIN;
    }

    // -- Roles --------------------------------------------------------------

    /** @return array<int, Role> */
    public function roles(): array
    {
        return $this->repo->allRoles();
    }

    public function findRole(int $id): ?Role
    {
        return $this->repo->findRole($id);
    }

    /** @return array<int, int> role id => permission count */
    public function permissionCounts(): array
    {
        return $this->repo->permissionCounts();
    }

    /**
     * Validate + create a role. Returns a list of field errors ([] on success).
     *
     * @param array{slug:string,name:string,rank:int} $data
     * @return array<string, string>
     */
    public function createRole(array $data): array
    {
        $errors = $this->validateRole($data);
        if ($errors !== []) {
            return $errors;
        }

        $this->repo->createRole($data['slug'], $data['name'], $data['rank']);

        return [];
    }

    /**
     * Validate + update a role. Core roles keep their locked slug.
     *
     * @param array{slug:string,name:string,rank:int} $data
     * @return array<string, string>
     */
    public function updateRole(Role $role, array $data): array
    {
        // Core role slugs are immutable — the engine matches on them.
        if ($this->isCoreRole($role->slug)) {
            $data['slug'] = $role->slug;
        }

        $errors = $this->validateRole($data, $role->id);
        if ($errors !== []) {
            return $errors;
        }

        $this->repo->updateRole($role->id, $data['slug'], $data['name'], $data['rank']);

        return [];
    }

    /**
     * @return array{ok:bool,error?:string}
     */
    public function deleteRole(Role $role): array
    {
        if ($this->isCoreRole($role->slug)) {
            return ['ok' => false, 'error' => 'نقش‌های پایه قابل حذف نیستند.'];
        }

        $this->repo->deleteRole($role->id);

        return ['ok' => true];
    }

    /**
     * Normalise + validate role input.
     *
     * @param array{slug:string,name:string,rank:int} $data
     * @return array<string, string>
     */
    private function validateRole(array $data, int $exceptId = 0): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'نام نقش الزامی است.';
        }
        if ($data['slug'] === '') {
            $errors['slug'] = 'شناسه (slug) الزامی است.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]{1,49}$/', $data['slug'])) {
            $errors['slug'] = 'شناسه باید با حرف انگلیسی آغاز شده و فقط شامل حروف کوچک، عدد و زیرخط باشد.';
        } elseif ($this->repo->slugTaken($data['slug'], $exceptId)) {
            $errors['slug'] = 'این شناسه قبلاً استفاده شده است.';
        }
        if ($data['rank'] < 0 || $data['rank'] > 1000) {
            $errors['rank'] = 'رتبه باید عددی بین ۰ تا ۱۰۰۰ باشد.';
        }

        return $errors;
    }

    public static function normaliseSlug(string $raw): string
    {
        $slug = strtolower(trim($raw));
        $slug = preg_replace('/[\s-]+/', '_', $slug) ?? '';

        return preg_replace('/[^a-z0-9_]/', '', $slug) ?? '';
    }

    // -- Permissions --------------------------------------------------------

    /** @return array<int, Permission> */
    public function permissions(): array
    {
        return $this->repo->allPermissions();
    }

    /**
     * Permissions grouped by category for the matrix view.
     *
     * @return array<string, array<int, Permission>>
     */
    public function permissionsByCategory(): array
    {
        $grouped = [];
        foreach ($this->repo->allPermissions() as $permission) {
            $grouped[$permission->category][] = $permission;
        }

        return $grouped;
    }

    /** @return array<int, int> permission ids granted to the role */
    public function permissionIdsForRole(int $roleId): array
    {
        return $this->repo->permissionIdsForRole($roleId);
    }

    /**
     * @param array<int, int|string> $permissionIds
     */
    public function saveRolePermissions(Role $role, array $permissionIds): void
    {
        // The Super Admin implicitly holds every permission (bypasses checks),
        // so storing rows for it is meaningless — skip to avoid confusion.
        if ($this->isSuperAdminRole($role->slug)) {
            return;
        }

        $this->repo->syncRolePermissions($role->id, array_map('intval', $permissionIds));
    }

    // -- Users --------------------------------------------------------------

    /** @return array<int, User> */
    public function users(int $limit, int $offset): array
    {
        return $this->repo->users($limit, $offset);
    }

    public function countUsers(): int
    {
        return $this->repo->countUsers();
    }

    public function findUser(int $id): ?User
    {
        return $this->repo->findUser($id);
    }

    /**
     * Assign a role to a user. Only slugs that exist in the `roles` table are
     * accepted, so the production `users.role` column can never receive an
     * out-of-range value.
     *
     * @return array{ok:bool,error?:string}
     */
    public function assignRole(int $userId, string $roleSlug): array
    {
        if ($this->repo->findRoleBySlug($roleSlug) === null) {
            return ['ok' => false, 'error' => 'نقش انتخاب‌شده معتبر نیست.'];
        }

        $this->repo->setUserRole($userId, $roleSlug);

        return ['ok' => true];
    }

    /** @return array<int, string> permission id => effect (allow|deny) */
    public function userOverrides(int $userId): array
    {
        return $this->repo->userOverrides($userId);
    }

    /**
     * @param array<int|string, string> $effects permission id => allow|deny|inherit
     */
    public function saveUserOverrides(int $userId, array $effects): void
    {
        $clean = [];
        foreach ($effects as $permissionId => $effect) {
            $clean[(int) $permissionId] = (string) $effect;
        }

        $this->repo->syncUserOverrides($userId, $clean);
    }
}
