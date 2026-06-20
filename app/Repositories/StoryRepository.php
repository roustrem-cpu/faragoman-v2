<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Story;

/**
 * Data access for the `stories` table. Uses `SELECT *` so it stays compatible
 * with the legacy schema regardless of which optional columns exist, and never
 * references columns (e.g. is_active) that may be missing on older databases.
 */
final class StoryRepository
{
    public function __construct(private Database $db)
    {
    }

    /**
     * All stories ordered exactly as the legacy admin panel expected them.
     *
     * @return array<int, Story>
     */
    public function all(): array
    {
        $rows = $this->db->select(
            'SELECT * FROM stories ORDER BY display_order ASC, created_at DESC'
        );

        return array_map([Story::class, 'fromRow'], $rows);
    }

    public function find(int $id): ?Story
    {
        $row = $this->db->selectOne('SELECT * FROM stories WHERE id = ? LIMIT 1', [$id]);

        return $row ? Story::fromRow($row) : null;
    }

    public function create(string $title, string $imageUrl, ?string $linkUrl, int $displayOrder = 0): int
    {
        $this->db->statement(
            'INSERT INTO stories (title, image_url, link_url, display_order, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$title, $imageUrl, $linkUrl, $displayOrder],
        );

        return $this->db->lastInsertId();
    }

    public function delete(int $id): int
    {
        return $this->db->statement('DELETE FROM stories WHERE id = ?', [$id]);
    }

    public function reorder(int $id, int $displayOrder): int
    {
        return $this->db->statement('UPDATE stories SET display_order = ? WHERE id = ?', [$displayOrder, $id]);
    }
}
