<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Article model mapped onto the existing `articles` table.
 */
final class Article
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $slug,
        public readonly ?string $excerpt,
        public readonly ?string $content,
        public readonly ?int $categoryId,
        public readonly ?int $userId,
        public readonly string $status,
        public readonly int $realReads = 0,
        public readonly ?string $createdAt = null,
        public readonly ?string $authorName = null,
        public readonly ?string $categoryName = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $postTag = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: $row['slug'] ?? null,
            excerpt: $row['excerpt'] ?? null,
            content: $row['content'] ?? null,
            categoryId: isset($row['category_id']) ? (int) $row['category_id'] : null,
            userId: isset($row['user_id']) ? (int) $row['user_id'] : null,
            status: (string) ($row['status'] ?? 'draft'),
            realReads: (int) ($row['real_reads'] ?? 0),
            createdAt: $row['created_at'] ?? null,
            authorName: $row['user_display_name'] ?? null,
            categoryName: $row['category_name'] ?? null,
            imageUrl: $row['image_url'] ?? null,
            postTag: $row['post_tag'] ?? null,
        );
    }
}
