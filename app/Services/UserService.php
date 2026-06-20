<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * User-management business logic for the admin panel (Task F).
 *
 * Wraps UserRepository with pagination, search and validation for the
 * list / edit-profile / ban-unban screens. Controllers stay thin; all SQL
 * stays in the repository.
 *
 * Scope is deliberately narrow: profile fields + ban state only. Role changes
 * live in the RBAC UI (Task E) and authentication stays in AuthService, so no
 * privileged column (password / role / author_rank) is ever written here.
 */
final class UserService
{
    private const PER_PAGE = 20;

    public function __construct(private UserRepository $users)
    {
    }

    public function find(int $id): ?User
    {
        return $this->users->find($id);
    }

    /**
     * @return array<int, User>
     */
    public function list(int $page, string $search = ''): array
    {
        $page = max(1, $page);

        return $this->users->paginate(self::PER_PAGE, ($page - 1) * self::PER_PAGE, $search);
    }

    public function totalPages(string $search = ''): int
    {
        return (int) max(1, (int) ceil($this->users->countAll($search) / self::PER_PAGE));
    }

    public function total(string $search = ''): int
    {
        return $this->users->countAll($search);
    }

    /**
     * Validate + persist editable profile fields.
     *
     * @param array{display_name:string,email:string,user_title:string,user_bio:string,avatar_url:string} $data
     * @return array<string, string> field errors ([] on success)
     */
    public function updateProfile(int $id, array $data): array
    {
        $errors = [];

        if ($data['email'] === '') {
            $errors['email'] = 'ایمیل الزامی است.';
        } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'ایمیل معتبر نیست.';
        } elseif ($this->users->emailTakenByOther($data['email'], $id)) {
            $errors['email'] = 'این ایمیل قبلاً برای کاربر دیگری ثبت شده است.';
        }
        if (mb_strlen($data['display_name']) > 255) {
            $errors['display_name'] = 'نام نمایشی نباید بیش از ۲۵۵ کاراکتر باشد.';
        }
        if (mb_strlen($data['user_title']) > 255) {
            $errors['user_title'] = 'عنوان نباید بیش از ۲۵۵ کاراکتر باشد.';
        }

        if ($errors !== []) {
            return $errors;
        }

        $this->users->updateProfile($id, $data);

        return [];
    }

    public function setBanned(int $id, bool $banned): void
    {
        $this->users->setBanned($id, $banned);
    }
}
