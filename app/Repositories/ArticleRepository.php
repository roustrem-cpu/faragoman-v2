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

    // ----------------------------------------------------------------------
    // Admin management (Phase 3, Task D). Writes mirror the legacy `articles`
    // columns exactly for 100% backward compatibility with the production DB.
    // ----------------------------------------------------------------------

    /**
     * All articles (any status) for the admin list.
     *
     * @return array<int, Article>
     */
    public function adminList(int $limit = 20, int $offset = 0): array
    {
        $rows = $this->db->select(
            'SELECT a.*, u.display_name AS user_display_name, c.name AS category_name
             FROM articles a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN categories c ON c.id = a.category_id
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset],
        );

        return array_map(static fn (array $r): Article => Article::fromRow($r), $rows);
    }

    public function adminCount(): int
    {
        $row = $this->db->selectOne('SELECT COUNT(*) AS total FROM articles');

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function allCategories(): array
    {
        return $this->db->select('SELECT id, name FROM categories ORDER BY name ASC');
    }

    /**
     * Insert a new article. Mirrors the legacy INSERT column set so every
     * NOT NULL column is satisfied; scrollytelling fields default to empty.
     *
     * @param array{user_id:int,author_name:string,category_id:int,title:string,post_tag:string,content:string,excerpt:string,image_url:string,status:string} $a
     */
    public function create(array $a): int
    {
        $this->db->statement(
            'INSERT INTO articles
                (user_id, author_name, category_id, title, post_tag, content, excerpt, image_url, status,
                 key_point_1, key_point_2, key_point_3, is_scrolly, scrolly_data)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $a['user_id'], $a['author_name'], $a['category_id'], $a['title'], $a['post_tag'],
                $a['content'], $a['excerpt'], $a['image_url'], $a['status'],
                '', '', '', 0, '',
            ],
        );

        return $this->db->lastInsertId();
    }

    /**
     * Update editable fields. Intentionally does NOT touch key_point_*,
     * is_scrolly, scrolly_data, author_name or status (preserved / managed
     * elsewhere) so no existing data is lost.
     *
     * @param array{category_id:int,title:string,post_tag:string,content:string,excerpt:string,image_url:string} $a
     */
    public function update(int $id, array $a): void
    {
        $this->db->statement(
            'UPDATE articles
             SET category_id = ?, title = ?, post_tag = ?, content = ?, excerpt = ?, image_url = ?
             WHERE id = ?',
            [$a['category_id'], $a['title'], $a['post_tag'], $a['content'], $a['excerpt'], $a['image_url'], $id],
        );
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM articles WHERE id = ?', [$id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->statement('UPDATE articles SET status = ? WHERE id = ?', [$status, $id]);
    }
}
