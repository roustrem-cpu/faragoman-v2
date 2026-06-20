<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Story model mapped onto the existing `stories` table.
 *
 * Backward compatibility: the legacy table only guarantees the columns
 * id, title, image_url, link_url, display_order, created_at. The optional
 * `is_active` flag is honoured when present and defaults to active so the
 * model works against both legacy and freshly-provisioned databases.
 */
final class Story
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $imageUrl,
        public readonly ?string $linkUrl = null,
        public readonly int $displayOrder = 0,
        public readonly bool $isActive = true,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            title: (string) ($row['title'] ?? ''),
            imageUrl: (string) ($row['image_url'] ?? ''),
            linkUrl: isset($row['link_url']) ? (string) $row['link_url'] : null,
            displayOrder: (int) ($row['display_order'] ?? 0),
            isActive: !array_key_exists('is_active', $row) || (bool) $row['is_active'],
            createdAt: $row['created_at'] ?? null,
        );
    }

    /**
     * Machine-readable representation used by the public JSON endpoint and
     * the front-end story viewer.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'image_url'     => $this->imageUrl,
            'link_url'      => $this->linkUrl,
            'display_order' => $this->displayOrder,
            'created_at'    => $this->createdAt,
        ];
    }
}
