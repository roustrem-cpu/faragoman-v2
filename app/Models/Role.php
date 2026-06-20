<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-only role model. Maps directly onto the additive `roles` table created
 * by database/schema.sql. No legacy table is touched.
 */
final class Role
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly int $rank,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            rank: (int) ($row['rank'] ?? 10),
            createdAt: $row['created_at'] ?? null,
        );
    }
}
