<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Story;
use App\Repositories\StoryRepository;
use App\Support\Cache;
use Throwable;

/**
 * Stories use cases. The active list is cached on the filesystem so the home
 * page pays the query cost at most once per cache window. Every public read is
 * defensive: if the `stories` table is missing (the feature was historically
 * disabled) the service returns an empty list instead of throwing, so the home
 * page never breaks.
 *
 * The admin-management methods (Task H) read uncached (so moderators always see
 * the live state) and flush the public cache on every mutation so the home ring
 * refreshes immediately.
 */
final class StoryService
{
    private const CACHE_TTL = 300; // seconds
    private const CACHE_KEY = 'stories:active';

    public function __construct(
        private StoryRepository $stories,
        private Cache $cache,
    ) {
    }

    /**
     * @return array<int, Story>
     */
    public function active(): array
    {
        try {
            return $this->cache->remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
                $all = $this->stories->all();

                return array_values(array_filter($all, static fn (Story $s): bool => $s->isActive));
            });
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Full, uncentred list for the admin panel (includes inactive stories).
     * Defensive: returns an empty list if the table is missing.
     *
     * @return array<int, Story>
     */
    public function all(): array
    {
        try {
            return $this->stories->all();
        } catch (Throwable) {
            return [];
        }
    }

    public function find(int $id): ?Story
    {
        return $this->stories->find($id);
    }

    public function supportsActiveFlag(): bool
    {
        try {
            return $this->stories->supportsActiveFlag();
        } catch (Throwable) {
            return false;
        }
    }

    public function create(string $title, string $imageUrl, ?string $linkUrl, int $displayOrder = 0): int
    {
        $id = $this->stories->create($title, $imageUrl, $linkUrl, $displayOrder);
        $this->cache->forget(self::CACHE_KEY);

        return $id;
    }

    public function update(int $id, string $title, string $imageUrl, ?string $linkUrl, int $displayOrder): void
    {
        $this->stories->update($id, $title, $imageUrl, $linkUrl, $displayOrder);
        $this->cache->forget(self::CACHE_KEY);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->stories->setActive($id, $active);
        $this->cache->forget(self::CACHE_KEY);
    }

    public function delete(int $id): void
    {
        $this->stories->delete($id);
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * Move a story one slot up or down. The whole list is re-indexed to a clean
     * sequential display_order (0..n) and the target swapped with its neighbour,
     * so ordering is deterministic even when the legacy data had duplicate or
     * gappy display_order values. Only the display_order column is written.
     */
    public function move(int $id, string $direction): void
    {
        $all = $this->stories->all();
        $ids = array_map(static fn (Story $s): int => $s->id, $all);

        $pos = array_search($id, $ids, true);
        if ($pos === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $pos - 1 : $pos + 1;
        if ($swapWith < 0 || $swapWith >= count($ids)) {
            return;
        }

        [$ids[$pos], $ids[$swapWith]] = [$ids[$swapWith], $ids[$pos]];

        foreach ($ids as $index => $storyId) {
            $this->stories->reorder($storyId, $index);
        }

        $this->cache->forget(self::CACHE_KEY);
    }
}
