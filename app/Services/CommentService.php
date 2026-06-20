<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Repositories\CommentRepository;

/**
 * Comment-moderation business logic for the admin panel (Task G).
 *
 * Wraps CommentRepository with pagination, status filtering and a safe status
 * whitelist for the list / approve / reject / delete screens. Controllers stay
 * thin; all SQL stays in the repository.
 *
 * Backward-compatible status mapping (CRITICAL): the legacy `comments.status`
 * column is only ever set by legacy code to 'pending' or 'approved'. To avoid
 * writing an out-of-range value to the production column we keep that exact
 * whitelist (same precedent as the Task D article-status decision):
 *   - approve → 'approved'  (publicly visible)
 *   - reject  → 'pending'   (removed from public view; reversible — mirrors the
 *               legacy "un-approve" semantics, since the public article query
 *               only shows 'approved' comments to everyone)
 *   - delete  → row removed
 */
final class CommentService
{
    private const PER_PAGE = 20;

    /** Filters the moderation list accepts ('all' maps to no status filter). */
    public const FILTERS = ['pending', 'approved', 'all'];

    public function __construct(private CommentRepository $comments)
    {
    }

    public function find(int $id): ?Comment
    {
        return $this->comments->find($id);
    }

    /**
     * @return array<int, Comment>
     */
    public function list(string $filter, int $page): array
    {
        $page = max(1, $page);

        return $this->comments->paginate(
            $this->statusFor($filter),
            self::PER_PAGE,
            ($page - 1) * self::PER_PAGE,
        );
    }

    public function total(string $filter): int
    {
        return $this->comments->countAll($this->statusFor($filter));
    }

    public function totalPages(string $filter): int
    {
        return (int) max(1, (int) ceil($this->total($filter) / self::PER_PAGE));
    }

    public function pendingCount(): int
    {
        return $this->comments->pendingCount();
    }

    public function approve(int $id): void
    {
        $this->comments->setStatus($id, 'approved');
    }

    public function reject(int $id): void
    {
        // Reject = remove from public view by reverting to the legacy 'pending'
        // state. No out-of-range value is ever written to the production column.
        $this->comments->setStatus($id, 'pending');
    }

    public function delete(int $id): void
    {
        $this->comments->delete($id);
    }

    public function normaliseFilter(string $filter): string
    {
        return in_array($filter, self::FILTERS, true) ? $filter : 'pending';
    }

    private function statusFor(string $filter): ?string
    {
        $filter = $this->normaliseFilter($filter);

        return $filter === 'all' ? null : $filter;
    }
}
