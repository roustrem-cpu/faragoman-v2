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

    /**
     * Published articles in a category.
     *
     * @return array<int, Article>
     */
    public function byCategory(int $categoryId, int $limit = 12, int $offset = 0): array
    {
        $rows = $this->db->select(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.status = ? AND a.category_id = ?
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?',
            ['published', $categoryId, $limit, $offset],
        );

        return array_map(static fn (array $r): Article => Article::fromRow($r), $rows);
    }

    public function countByCategory(int $categoryId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM articles WHERE status = 'published' AND category_id = ?",
            [$categoryId],
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Published articles by an author.
     *
     * @return array<int, Article>
     */
    public function byAuthor(int $userId, int $limit = 12, int $offset = 0): array
    {
        $rows = $this->db->select(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.status = ? AND a.user_id = ?
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?',
            ['published', $userId, $limit, $offset],
        );

        return array_map(static fn (array $r): Article => Article::fromRow($r), $rows);
    }

    public function countByAuthor(int $userId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM articles WHERE status = 'published' AND user_id = ?",
            [$userId],
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Full-text-ish search across published articles (title, excerpt, body).
     * User input wildcards are neutralised with an explicit ESCAPE clause.
     *
     * @return array<int, Article>
     */
    public function search(string $term, int $limit = 12, int $offset = 0): array
    {
        $like = '%' . $this->escapeLike($term) . '%';

        $rows = $this->db->select(
            "SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             WHERE a.status = ?
               AND (a.title LIKE ? ESCAPE '!' OR a.excerpt LIKE ? ESCAPE '!' OR a.content LIKE ? ESCAPE '!')
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?",
            ['published', $like, $like, $like, $limit, $offset],
        );

        return array_map(static fn (array $r): Article => Article::fromRow($r), $rows);
    }

    public function countSearch(string $term): int
    {
        $like = '%' . $this->escapeLike($term) . '%';

        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM articles
             WHERE status = 'published'
               AND (title LIKE ? ESCAPE '!' OR excerpt LIKE ? ESCAPE '!' OR content LIKE ? ESCAPE '!')",
            [$like, $like, $like],
        );

        return (int) ($row['total'] ?? 0);
    }

    public function findCategoryName(int $categoryId): ?string
    {
        $row = $this->db->selectOne('SELECT name FROM categories WHERE id = ? LIMIT 1', [$categoryId]);

        return isset($row['name']) ? (string) $row['name'] : null;
    }

    public function findAuthorName(int $userId): ?string
    {
        $row = $this->db->selectOne('SELECT display_name FROM users WHERE id = ? LIMIT 1', [$userId]);

        return isset($row['display_name']) ? (string) $row['display_name'] : null;
    }

    /**
     * Escape LIKE wildcards in user-supplied search terms (paired with
     * `ESCAPE '!'` in the queries above) so `%` / `_` are matched literally.
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
