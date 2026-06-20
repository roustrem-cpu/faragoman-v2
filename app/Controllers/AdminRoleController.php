<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Role;
use App\Services\AuthService;
use App\Services\RbacService;
use App\Support\Rbac;

/**
 * Admin — Dynamic RBAC Management UI (Task E).
 *
 * Lets the Super Administrator (or any role granted `roles.manage`) create and
 * edit roles, assign permissions to roles, assign roles to users, and apply
 * per-user permission overrides. Access is gated upstream by
 * [AuthMiddleware, gate.roles]; write routes additionally pass through
 * CsrfMiddleware.
 *
 * All persistence flows through RbacService → RbacRepository against the
 * additive RBAC tables; assigning a role only writes the existing
 * `users.role` string column — zero schema changes, 100% DB backward compatible.
 */
final class AdminRoleController extends Controller
{
    public function __construct(
        View $view,
        private RbacService $rbac,
        private AuthService $auth,
        private Rbac $engine,
    ) {
        parent::__construct($view);
    }

    // -- Roles --------------------------------------------------------------

    public function index(Request $request): Response
    {
        return $this->renderWith('layouts/admin', 'admin/roles/index', [
            'title'       => 'نقش‌ها و دسترسی‌ها — فراگمان',
            'heading'     => 'نقش‌ها و دسترسی‌ها',
            'activeNav'   => 'roles',
            'currentUser' => $this->auth->userModel(),
            'ready'       => $this->rbac->ready(),
            'roles'       => $this->rbac->ready() ? $this->rbac->roles() : [],
            'counts'      => $this->rbac->ready() ? $this->rbac->permissionCounts() : [],
            'permTotal'   => $this->rbac->ready() ? count($this->rbac->permissions()) : 0,
            'flash'       => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function create(Request $request): Response
    {
        if (!$this->rbac->ready()) {
            return Response::redirect('/admin/roles');
        }

        return $this->roleForm(null);
    }

    public function store(Request $request): Response
    {
        if (!$this->rbac->ready()) {
            return Response::redirect('/admin/roles');
        }

        $data   = $this->collectRole($request);
        $errors = $this->rbac->createRole($data);

        if ($errors !== []) {
            return $this->roleForm(null, $data, $errors, 422);
        }

        return Response::redirect('/admin/roles?m=role_created');
    }

    public function edit(Request $request, string $id): Response
    {
        $role = $this->rbac->findRole((int) $id);

        if ($role === null) {
            return $this->notFound();
        }

        return $this->roleForm($role);
    }

    public function update(Request $request, string $id): Response
    {
        $role = $this->rbac->findRole((int) $id);

        if ($role === null) {
            return $this->notFound();
        }

        $data   = $this->collectRole($request);
        $errors = $this->rbac->updateRole($role, $data);

        if ($errors !== []) {
            return $this->roleForm($role, $data, $errors, 422);
        }

        return Response::redirect('/admin/roles?m=role_updated');
    }

    public function destroy(Request $request, string $id): Response
    {
        $role = $this->rbac->findRole((int) $id);

        if ($role === null) {
            return $this->notFound();
        }

        $result = $this->rbac->deleteRole($role);

        return Response::redirect('/admin/roles?m=' . ($result['ok'] ? 'role_deleted' : 'role_locked'));
    }

    // -- Role permissions ---------------------------------------------------

    public function permissions(Request $request, string $id): Response
    {
        $role = $this->rbac->findRole((int) $id);

        if ($role === null) {
            return $this->notFound();
        }

        return $this->renderWith('layouts/admin', 'admin/roles/permissions', [
            'title'        => 'دسترسی‌های نقش — فراگمان',
            'heading'      => 'دسترسی‌های نقش: ' . $role->name,
            'activeNav'    => 'roles',
            'currentUser'  => $this->auth->userModel(),
            'role'         => $role,
            'isSuperAdmin' => $this->rbac->isSuperAdminRole($role->slug),
            'groups'       => $this->rbac->permissionsByCategory(),
            'selected'     => $this->rbac->permissionIdsForRole($role->id),
            'categories'   => $this->categoryLabels(),
        ]);
    }

    public function savePermissions(Request $request, string $id): Response
    {
        $role = $this->rbac->findRole((int) $id);

        if ($role === null) {
            return $this->notFound();
        }

        $ids = $request->input('permissions', []);
        $this->rbac->saveRolePermissions($role, is_array($ids) ? $ids : []);

        return Response::redirect('/admin/roles?m=perms_saved');
    }

    // -- Users & role assignment -------------------------------------------

    public function users(Request $request): Response
    {
        if (!$this->rbac->ready()) {
            return Response::redirect('/admin/roles');
        }

        $perPage = 20;
        $page    = max(1, (int) $request->query('page_num', 1));
        $total   = $this->rbac->countUsers();
        $users   = $this->rbac->users($perPage, ($page - 1) * $perPage);

        return $this->renderWith('layouts/admin', 'admin/roles/users', [
            'title'       => 'تخصیص نقش به کاربران — فراگمان',
            'heading'     => 'تخصیص نقش به کاربران',
            'activeNav'   => 'roles',
            'currentUser' => $this->auth->userModel(),
            'users'       => $users,
            'roles'       => $this->rbac->roles(),
            'engine'      => $this->engine,
            'selfId'      => $this->auth->id() ?? 0,
            'page'        => $page,
            'totalPages'  => (int) max(1, (int) ceil($total / $perPage)),
            'flash'       => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function assignRole(Request $request, string $id): Response
    {
        $userId = (int) $id;

        // Self-lockout guard: never let an admin change their own role.
        if ($userId === ($this->auth->id() ?? 0)) {
            return Response::redirect('/admin/roles/users?m=self_blocked');
        }

        if ($this->rbac->findUser($userId) === null) {
            return $this->notFound();
        }

        $result = $this->rbac->assignRole($userId, (string) $request->input('role', ''));

        return Response::redirect('/admin/roles/users?m=' . ($result['ok'] ? 'role_assigned' : 'role_invalid'));
    }

    // -- Per-user overrides -------------------------------------------------

    public function overrides(Request $request, string $id): Response
    {
        $user = $this->rbac->findUser((int) $id);

        if ($user === null) {
            return $this->notFound();
        }

        return $this->renderWith('layouts/admin', 'admin/roles/overrides', [
            'title'       => 'دسترسی‌های اختصاصی کاربر — فراگمان',
            'heading'     => 'دسترسی‌های اختصاصی: ' . $user->name(),
            'activeNav'   => 'roles',
            'currentUser' => $this->auth->userModel(),
            'targetUser'  => $user,
            'normalRole'  => $this->engine->normaliseRole($user->role),
            'isSuperAdmin' => $this->engine->isSuperAdmin($user->role),
            'groups'      => $this->rbac->permissionsByCategory(),
            'overrides'   => $this->rbac->userOverrides($user->id),
            'categories'  => $this->categoryLabels(),
        ]);
    }

    public function saveOverrides(Request $request, string $id): Response
    {
        $user = $this->rbac->findUser((int) $id);

        if ($user === null) {
            return $this->notFound();
        }

        $effects = $request->input('effect', []);
        $this->rbac->saveUserOverrides($user->id, is_array($effects) ? $effects : []);

        return Response::redirect('/admin/roles/users?m=overrides_saved');
    }

    // ------------------------------------------------------------------ //

    /**
     * @param array<string, mixed>  $old
     * @param array<string, string> $errors
     */
    private function roleForm(?Role $role, array $old = [], array $errors = [], int $status = 200): Response
    {
        $isEdit = $role !== null;

        return $this->renderWith('layouts/admin', 'admin/roles/form', [
            'title'       => ($isEdit ? 'ویرایش نقش' : 'نقش جدید') . ' — فراگمان',
            'heading'     => $isEdit ? 'ویرایش نقش' : 'نقش جدید',
            'activeNav'   => 'roles',
            'currentUser' => $this->auth->userModel(),
            'role'        => $role,
            'isEdit'      => $isEdit,
            'isCore'      => $isEdit && $this->rbac->isCoreRole($role->slug),
            'formAction'  => $isEdit ? '/admin/roles/' . $role->id : '/admin/roles',
            'old'         => $old,
            'errors'      => $errors,
        ], $status);
    }

    /**
     * @return array{slug:string,name:string,rank:int}
     */
    private function collectRole(Request $request): array
    {
        return [
            'slug' => RbacService::normaliseSlug((string) $request->input('slug', '')),
            'name' => trim((string) $request->input('name', '')),
            'rank' => (int) $request->input('rank', 10),
        ];
    }

    private function notFound(): Response
    {
        return $this->render('errors.404', [
            'title'       => 'یافت نشد — فراگمان',
            'currentUser' => $this->auth->userModel(),
        ], 404);
    }

    /** @return array<string, string> */
    private function categoryLabels(): array
    {
        return [
            'content'  => 'محتوا',
            'comments' => 'دیدگاه‌ها',
            'stories'  => 'استوری‌ها',
            'users'    => 'کاربران',
            'roles'    => 'نقش‌ها و دسترسی',
            'settings' => 'تنظیمات',
            'general'  => 'عمومی',
        ];
    }

    private function flash(string $m): ?string
    {
        return match ($m) {
            'role_created'    => 'نقش جدید ایجاد شد.',
            'role_updated'    => 'تغییرات نقش ذخیره شد.',
            'role_deleted'    => 'نقش حذف شد.',
            'role_locked'     => 'این نقش پایه است و قابل حذف نیست.',
            'perms_saved'     => 'دسترسی‌های نقش به‌روزرسانی شد.',
            'role_assigned'   => 'نقش کاربر تغییر کرد.',
            'role_invalid'    => 'نقش انتخاب‌شده معتبر نیست.',
            'self_blocked'    => 'نمی‌توانید نقش حساب خودتان را تغییر دهید.',
            'overrides_saved' => 'دسترسی‌های اختصاصی کاربر ذخیره شد.',
            default           => null,
        };
    }
}
