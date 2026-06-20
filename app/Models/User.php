<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Read-only user model. Maps directly onto the existing `users` table columns
 * so no schema change is required.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $displayName,
        public readonly ?string $avatarUrl,
        public readonly bool $isBanned,
        public readonly ?string $userTitle = null,
        public readonly ?string $userBio = null,
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
            username: (string) $row['username'],
            email: (string) ($row['email'] ?? ''),
            role: (string) ($row['role'] ?? 'user'),
            displayName: $row['display_name'] ?? null,
            avatarUrl: $row['avatar_url'] ?? null,
            isBanned: (bool) ($row['is_banned'] ?? false),
            userTitle: $row['user_title'] ?? null,
            userBio: $row['user_bio'] ?? null,
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function name(): string
    {
        return $this->displayName ?: $this->username;
    }
}
