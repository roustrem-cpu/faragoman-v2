<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-only knowledge-base entry mapped onto the legacy `wiki_terms` table.
 *
 * Backward compatibility: columns recovered from the legacy `project` repo
 * (includes/wiki_functions.php + pages/wiki.php): id, term, slug, brief_desc,
 * full_content, display_type, status, updated_at. Only published entries are
 * ever exposed. `display_type === 'full_page'` marks a term that has its own
 * dedicated page; other terms are glossary-only (brief tooltip).
 */
final class Wiki
{
    public function __construct(
        public readonly int $id,
        public readonly string $term,
        public readonly string $slug,
        public readonly string $briefDesc,
        public readonly ?string $fullContent = null,
        public readonly string $displayType = 'tooltip',
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            term: (string) ($row['term'] ?? ''),
            slug: (string) ($row['slug'] ?? ''),
            briefDesc: (string) ($row['brief_desc'] ?? ''),
            fullContent: isset($row['full_content']) ? (string) $row['full_content'] : null,
            displayType: (string) ($row['display_type'] ?? 'tooltip'),
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    public function isFullPage(): bool
    {
        return $this->displayType === 'full_page';
    }
}
