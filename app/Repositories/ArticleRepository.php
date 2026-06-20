<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Article;

/**
 * All reads/writes against `articles`. Queries join the author and category in
 * a single statement to avoid the N+1 problems present in the legacy code.
 */
final class ArticleRepository
{
    public function __construct(private Database $db)
    {
    }

    /**
     * Latest published articles for the home feed.
     *
     * @return array<int, Article>
     */
    public function latestPublished(int $limit = 12, int $offset = 0): array
    {
        $rows = $this->db->select(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.status = ?
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?',
            ['published', $limit, $offset],
        );

        return array_map(static fn (array $r): Article => Article::fromRow($r), $rows);
    }

    public function findByTitle(string $title): ?Article
    {
        $row = $this->db->selectOne(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.title = ? LIMIT 1',
            [$title],
        );

        return $row ? Article::fromRow($row) : null;
    }

    public function find(int $id): ?Article
    {
        $row = $this->db->selectOne(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.id = ? LIMIT 1',
            [$id],
        );

        return $row ? Article::fromRow($row) : null;
    }

    public function countPublished(): int
    {
        $row = $this->db->selectOne("SELECT COUNT(*) AS total FROM articles WHERE status = 'published'");

        return (int) ($row['total'] ?? 0);
    }
}
