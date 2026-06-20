<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Story;
use Throwable;

/**
 * Data access for the `stories` table. Uses `SELECT *` so it stays compatible
 * with the legacy schema regardless of which optional columns exist, and never
 * references columns (e.g. is_active) that may be missing on older databases
 * unless their presence has first been confirmed via {@see self::supportsActiveFlag()}.
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

    /**
     * Update the always-present, legacy-guaranteed columns only (title,
     * image_url, link_url, display_order). The optional is_active flag is
     * toggled separately via {@see self::setActive()} so this write never
     * references a column that might be missing on older databases.
     */
    public function update(int $id, string $title, string $imageUrl, ?string $linkUrl, int $displayOrder): int
    {
        return $this->db->statement(
            'UPDATE stories SET title = ?, image_url = ?, link_url = ?, display_order = ? WHERE id = ?',
            [$title, $imageUrl, $linkUrl, $displayOrder, $id],
        );
    }

    public function delete(int $id): int
    {
        return $this->db->statement('DELETE FROM stories WHERE id = ?', [$id]);
    }

    public function reorder(int $id, int $displayOrder): int
    {
        return $this->db->statement('UPDATE stories SET display_order = ? WHERE id = ?', [$displayOrder, $id]);
    }

    /**
     * Toggle the OPTIONAL `is_active` flag. On legacy databases where the column
     * was never added this is a safe no-op (returns 0) — the model already
     * treats a missing column as "active", so visibility is unchanged.
     */
    public function setActive(int $id, bool $active): int
    {
        if (!$this->supportsActiveFlag()) {
            return 0;
        }

        return $this->db->statement('UPDATE stories SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    /**
     * Detects whether the additive `is_active` column exists on this database.
     * Probing with a guarded SELECT keeps the app working on both legacy tables
     * (no column) and freshly provisioned ones (column present) — mirroring the
     * graceful-degradation philosophy used across the RBAC layer.
     */
    public function supportsActiveFlag(): bool
    {
        try {
            $this->db->selectOne('SELECT is_active FROM stories LIMIT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
