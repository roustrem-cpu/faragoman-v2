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
 * disabled) the service returns an empty list instead of throwing, so the
 * home page never breaks.
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

    public function create(string $title, string $imageUrl, ?string $linkUrl, int $displayOrder = 0): int
    {
        $id = $this->stories->create($title, $imageUrl, $linkUrl, $displayOrder);
        $this->cache->forget(self::CACHE_KEY);

        return $id;
    }

    public function delete(int $id): void
    {
        $this->stories->delete($id);
        $this->cache->forget(self::CACHE_KEY);
    }
}
