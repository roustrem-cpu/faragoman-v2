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

    // -- Admin user management (Task F) ------------------------------------

    /**
     * Paginated, optionally searched user list for the admin panel. The search
     * matches username / email / display_name; user wildcards are neutralised
     * with a paired `ESCAPE '!'` (same convention as the Task B search).
     *
     * @return array<int, User>
     */
    public function paginate(int $limit, int $offset, string $search = ''): array
    {
        [$where, $params] = $this->searchClause($search);
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->select(
            'SELECT ' . self::COLUMNS . ' FROM users' . $where . ' ORDER BY id DESC LIMIT ? OFFSET ?',
            $params,
        );

        return array_map(static fn (array $r): User => User::fromRow($r), $rows);
    }

    public function countAll(string $search = ''): int
    {
        [$where, $params] = $this->searchClause($search);
        $row = $this->db->selectOne('SELECT COUNT(*) AS c FROM users' . $where, $params);

        return (int) ($row['c'] ?? 0);
    }

    public function emailTakenByOther(string $email, int $exceptId): bool
    {
        return $this->db->selectOne(
            'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1',
            [$email, $exceptId],
        ) !== null;
    }

    /**
     * Update editable profile fields only. Deliberately never touches
     * `username`, `password`, `role`, `author_rank` or `is_banned` — those are
     * managed elsewhere (RBAC / auth) so no privileged column is changed here.
     *
     * @param array{display_name:string,email:string,user_title:string,user_bio:string,avatar_url:string} $f
     */
    public function updateProfile(int $id, array $f): void
    {
        $this->db->statement(
            'UPDATE users SET display_name = ?, email = ?, user_title = ?, user_bio = ?, avatar_url = ? WHERE id = ?',
            [$f['display_name'], $f['email'], $f['user_title'], $f['user_bio'], $f['avatar_url'], $id],
        );
    }

    public function setBanned(int $id, bool $banned): void
    {
        $this->db->statement('UPDATE users SET is_banned = ? WHERE id = ?', [$banned ? 1 : 0, $id]);
    }

    /**
     * @return array{0:string,1:array<int,mixed>} [whereSql, params]
     */
    private function searchClause(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return ['', []];
        }

        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';

        return [
            " WHERE username LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!' OR display_name LIKE ? ESCAPE '!'",
            [$like, $like, $like],
        ];
    }
}
