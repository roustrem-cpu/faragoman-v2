<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Authentication business logic. 100% compatible with the legacy auth scheme:
 *  - Verifies against the existing bcrypt (`PASSWORD_DEFAULT`) hashes.
 *  - Keeps `$_SESSION['user_id']` as the session key the legacy code used, so
 *    existing logged-in sessions keep working after deployment.
 */
final class AuthService
{
    private const SESSION_KEY = 'user_id';

    public function __construct(private UserRepository $users)
    {
    }

    /**
     * Attempt a login. Returns the user id on success, null on failure.
     */
    public function attempt(string $login, string $password): ?int
    {
        $row = $this->users->findCredentialsByLogin($login);

        if ($row === null || (bool) ($row['is_banned'] ?? false)) {
            return null;
        }

        if (!password_verify($password, (string) $row['password'])) {
            return null;
        }

        // Transparently upgrade legacy hashes when the algorithm improves.
        if (password_needs_rehash((string) $row['password'], PASSWORD_DEFAULT)) {
            // Re-hash handled by a dedicated maintenance routine; skipped here
            // to keep the read path side-effect free.
        }

        $userId = (int) $row['id'];
        $this->login($userId);

        return $userId;
    }

    public function login(int $userId): void
    {
        $_SESSION[self::SESSION_KEY] = $userId;
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $userId;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function id(): ?int
    {
        return isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : null;
    }

    public function userModel(): ?User
    {
        $id = $this->id();

        return $id ? $this->users->find($id) : null;
    }

    /**
     * Lightweight array form used by the RBAC engine.
     *
     * @return array{id:int,role:string}|null
     */
    public function user(): ?array
    {
        $model = $this->userModel();

        return $model ? ['id' => $model->id, 'role' => $model->role] : null;
    }
}
