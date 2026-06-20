<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Wiki;

/**
 * Data access for the legacy `wiki_terms` table. Reads published entries only.
 * The list query selects the exact columns the legacy cache builder used; the
 * single-term query uses `SELECT *` so it stays compatible regardless of which
 * optional columns the legacy table carries.
 */
final class WikiRepository
{
    public function __construct(private Database $db)
    {
    }

    /**
     * All published terms, ordered alphabetically for the glossary index.
     *
     * @return array<int, Wiki>
     */
    public function publishedList(): array
    {
        $rows = $this->db->select(
            "SELECT id, term, slug, brief_desc, display_type, updated_at
             FROM wiki_terms
             WHERE status = 'published'
             ORDER BY term ASC"
        );

        return array_map([Wiki::class, 'fromRow'], $rows);
    }

    public function findBySlug(string $slug): ?Wiki
    {
        $row = $this->db->selectOne(
            "SELECT * FROM wiki_terms WHERE slug = ? AND status = 'published' LIMIT 1",
            [$slug],
        );

        return $row ? Wiki::fromRow($row) : null;
    }
}
