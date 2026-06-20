<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-only permission model. Maps onto the additive `permissions` table.
 */
final class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $category = 'general',
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
            category: (string) ($row['category'] ?? 'general'),
        );
    }
}
