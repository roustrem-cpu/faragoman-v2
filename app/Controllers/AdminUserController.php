<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Support\Rbac;

/**
 * Admin — User Management (Task F).
 *
 * List/search users, edit their profile fields, and ban/unban accounts under
 * `/admin/users`. Access is gated upstream by [AuthMiddleware, gate.users]
 * (the `users.manage` permission); write routes additionally pass through
 * CsrfMiddleware.
 *
 * Backward compatible: only existing `users` columns are written
 * (display_name, email, user_title, user_bio, avatar_url, is_banned). Roles are
 * managed in the RBAC UI (Task E); passwords stay in AuthService.
 */
final class AdminUserController extends Controller
{
    public function __construct(
        View $view,
        private UserService $users,
        private AuthService $auth,
        private Rbac $engine,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $page   = max(1, (int) $request->query('page_num', 1));

        return $this->renderWith('layouts/admin', 'admin/users/index', [
            'title'       => 'مدیریت کاربران — فراگمان',
            'heading'     => 'مدیریت کاربران',
            'activeNav'   => 'users',
            'currentUser' => $this->auth->userModel(),
            'users'       => $this->users->list($page, $search),
            'engine'      => $this->engine,
            'selfId'      => $this->auth->id() ?? 0,
            'search'      => $search,
            'total'       => $this->users->total($search),
            'page'        => $page,
            'totalPages'  => $this->users->totalPages($search),
            'flash'       => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id);

        if ($user === null) {
            return $this->notFound();
        }

        return $this->form($user);
    }

    public function update(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id);

        if ($user === null) {
            return $this->notFound();
        }

        $data   = $this->collect($request);
        $errors = $this->users->updateProfile($user->id, $data);

        if ($errors !== []) {
            return $this->form($user, $data, $errors, 422);
        }

        return Response::redirect('/admin/users?m=updated');
    }

    public function ban(Request $request, string $id): Response
    {
        return $this->toggleBan((int) $id, true);
    }

    public function unban(Request $request, string $id): Response
    {
        return $this->toggleBan((int) $id, false);
    }

    // ------------------------------------------------------------------ //

    private function toggleBan(int $id, bool $banned): Response
    {
        // Self-lockout guard: never let an admin ban their own account.
        if ($banned && $id === ($this->auth->id() ?? 0)) {
            return Response::redirect('/admin/users?m=self_blocked');
        }

        if ($this->users->find($id) === null) {
            return $this->notFound();
        }

        $this->users->setBanned($id, $banned);

        return Response::redirect('/admin/users?m=' . ($banned ? 'banned' : 'unbanned'));
    }

    /**
     * @param array<string, mixed>  $old
     * @param array<string, string> $errors
     */
    private function form(User $user, array $old = [], array $errors = [], int $status = 200): Response
    {
        return $this->renderWith('layouts/admin', 'admin/users/form', [
            'title'       => 'ویرایش کاربر — فراگمان',
            'heading'     => 'ویرایش کاربر: ' . $user->name(),
            'activeNav'   => 'users',
            'currentUser' => $this->auth->userModel(),
            'user'        => $user,
            'role'        => $this->engine->normaliseRole($user->role),
            'formAction'  => '/admin/users/' . $user->id,
            'old'         => $old,
            'errors'      => $errors,
        ], $status);
    }

    /**
     * @return array{display_name:string,email:string,user_title:string,user_bio:string,avatar_url:string}
     */
    private function collect(Request $request): array
    {
        return [
            'display_name' => trim((string) $request->input('display_name', '')),
            'email'        => trim((string) $request->input('email', '')),
            'user_title'   => trim((string) $request->input('user_title', '')),
            'user_bio'     => trim((string) $request->input('user_bio', '')),
            'avatar_url'   => trim((string) $request->input('avatar_url', '')),
        ];
    }

    private function notFound(): Response
    {
        return $this->render('errors.404', [
            'title'       => 'یافت نشد — فراگمان',
            'currentUser' => $this->auth->userModel(),
        ], 404);
    }

    private function flash(string $m): ?string
    {
        return match ($m) {
            'updated'      => 'پروفایل کاربر به‌روزرسانی شد.',
            'banned'       => 'کاربر مسدود شد.',
            'unbanned'     => 'کاربر از حالت مسدود خارج شد.',
            'self_blocked' => 'نمی‌توانید حساب خودتان را مسدود کنید.',
            default        => null,
        };
    }
}
