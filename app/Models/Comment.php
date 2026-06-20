<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-only comment model. Maps directly onto the existing legacy `comments`
 * table columns (id, user_id, guest_name, guest_email, article_id, parent_id,
 * comment, status, created_at) so no schema change is required.
 *
 * The optional joined fields (author display name + article title) are
 * populated by the repository's admin list query and default to null when
 * absent — exactly mirroring the legacy admin moderation SELECT.
 */
final class Comment
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $userId,
        public readonly ?string $guestName,
        public readonly int $articleId,
        public readonly ?int $parentId,
        public readonly string $comment,
        public readonly string $status,
        public readonly ?string $createdAt = null,
        public readonly ?string $authorDisplayName = null,
        public readonly ?string $articleTitle = null,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: isset($row['user_id']) && $row['user_id'] !== null ? (int) $row['user_id'] : null,
            guestName: $row['guest_name'] ?? null,
            articleId: (int) ($row['article_id'] ?? 0),
            parentId: isset($row['parent_id']) && $row['parent_id'] !== null ? (int) $row['parent_id'] : null,
            comment: (string) ($row['comment'] ?? ''),
            status: (string) ($row['status'] ?? 'pending'),
            createdAt: $row['created_at'] ?? null,
            authorDisplayName: $row['display_name'] ?? null,
            articleTitle: $row['article_title'] ?? null,
        );
    }

    /**
     * Best-effort author label: registered display name, else guest name, else
     * a neutral fallback (matches the legacy admin list behaviour).
     */
    public function authorName(): string
    {
        $name = $this->authorDisplayName ?: $this->guestName;

        return ($name !== null && $name !== '') ? $name : 'ناشناس';
    }

    public function isGuest(): bool
    {
        return $this->userId === null;
    }
}
