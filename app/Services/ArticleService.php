<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Repositories\ArticleRepository;
use App\Support\Cache;

/**
 * Article-related use cases. The home feed is cached on the filesystem so the
 * heavy join query runs at most once per cache window — a big win on shared
 * hosting under traffic spikes.
 */
final class ArticleService
{
    private const FEED_CACHE_TTL = 120; // seconds

    public function __construct(
        private ArticleRepository $articles,
        private Cache $cache,
    ) {
    }

    /**
     * @return array<int, Article>
     */
    public function homeFeed(int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $key = "feed:home:{$page}:{$perPage}";

        return $this->cache->remember(
            $key,
            self::FEED_CACHE_TTL,
            fn (): array => $this->articles->latestPublished($perPage, $offset),
        );
    }

    public function totalPages(int $perPage = 12): int
    {
        $total = $this->cache->remember(
            'feed:home:count',
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->countPublished(),
        );

        return (int) max(1, (int) ceil($total / $perPage));
    }

    public function findByTitle(string $title): ?Article
    {
        return $this->articles->findByTitle($title);
    }

    public function find(int $id): ?Article
    {
        return $this->articles->find($id);
    }
}
