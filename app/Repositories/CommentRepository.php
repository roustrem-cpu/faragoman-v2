<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Comment;

/**
 * Encapsulates every `comments` table query. The rest of the app never writes
 * raw SQL against comments — that is the whole point of the Repository Pattern.
 *
 * Backward compatibility (CRITICAL): `comments` is the legacy production table.
 * The column set was recovered from the legacy `project` repo
 * (public_html/index.php INSERT, includes/chat_functions.php and
 * pages/admin/comments.php): id, user_id, guest_name, guest_email, article_id,
 * parent_id, comment, status, created_at. Reads/writes here touch only those
 * columns. The status column is only ever set to values the legacy code itself
 * uses ('approved' / 'pending'), so no out-of-range value can ever be written
 * to the production column.
 */
final class CommentRepository
{
    /** Status values known to exist on the legacy `comments.status` column. */
    public const STATUSES = ['pending', 'approved'];

    public function __construct(private Database $db)
    {
    }

    /**
     * Paginated moderation list. Mirrors the legacy admin SELECT: LEFT JOIN
     * users for the display name (guests have a NULL user_id) and JOIN articles
     * for the title. When $status is null every status is returned; otherwise
     * the list is filtered to that single status.
     *
     * @return array<int, Comment>
     */
    public function paginate(?string $status, int $limit, int $offset): array
    {
        [$where, $params] = $this->statusClause($status);
        $params[] = $limit;
        $params[] = $offset;

        $rows = $this->db->select(
            'SELECT c.id, c.user_id, c.guest_name, c.article_id, c.parent_id, c.comment, c.status, c.created_at,
                    u.display_name, a.title AS article_title
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             JOIN articles a ON c.article_id = a.id'
            . $where .
            ' ORDER BY c.created_at DESC LIMIT ? OFFSET ?',
            $params,
        );

        return array_map(static fn (array $r): Comment => Comment::fromRow($r), $rows);
    }

    public function countAll(?string $status): int
    {
        [$where, $params] = $this->statusClause($status);
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS c FROM comments c JOIN articles a ON c.article_id = a.id' . $where,
            $params,
        );

        return (int) ($row['c'] ?? 0);
    }

    public function find(int $id): ?Comment
    {
        $row = $this->db->selectOne(
            'SELECT c.id, c.user_id, c.guest_name, c.article_id, c.parent_id, c.comment, c.status, c.created_at,
                    u.display_name, a.title AS article_title
             FROM comments c
             LEFT JOIN users u ON c.user_id = u.id
             LEFT JOIN articles a ON c.article_id = a.id
             WHERE c.id = ? LIMIT 1',
            [$id],
        );

        return $row ? Comment::fromRow($row) : null;
    }

    /**
     * Number of comments awaiting moderation (drives the moderation filter badge).
     */
    public function pendingCount(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS c FROM comments WHERE status = 'pending'", []);

        return (int) ($row['c'] ?? 0);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->statement('UPDATE comments SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM comments WHERE id = ?', [$id]);
    }

    /**
     * @return array{0:string,1:array<int,mixed>} [whereSql, params]
     */
    private function statusClause(?string $status): array
    {
        if ($status === null) {
            return ['', []];
        }

        return [' WHERE c.status = ?', [$status]];
    }
}
