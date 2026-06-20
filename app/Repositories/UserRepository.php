<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

/**
 * Encapsulates every `users` table query. The rest of the app never writes
 * raw SQL against users — that is the whole point of the repository pattern.
 */
final class UserRepository
{
    private const COLUMNS = 'id, username, email, role, display_name, avatar_url, is_banned, author_rank, user_title, user_bio, created_at';

    public function __construct(private Database $db)
    {
    }

    public function find(int $id): ?User
    {
        $row = $this->db->selectOne(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE id = ? LIMIT 1',
            [$id],
        );

        return $row ? User::fromRow($row) : null;
    }

    /**
     * Returns the raw row (including the password hash) for authentication.
     *
     * @return array<string, mixed>|null
     */
    public function findCredentialsByLogin(string $login): ?array
    {
        return $this->db->selectOne(
            'SELECT id, username, email, password, role, is_banned FROM users
             WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login],
        );
    }

    public function emailExists(string $email): bool
    {
        return $this->db->selectOne('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]) !== null;
    }

    public function create(string $username, string $email, string $passwordHash): int
    {
        $this->db->statement(
            "INSERT INTO users (username, email, password, role, created_at)
             VALUES (?, ?, ?, 'user', NOW())",
            [$username, $email, $passwordHash],
        );

        return $this->db->lastInsertId();
    }
}
