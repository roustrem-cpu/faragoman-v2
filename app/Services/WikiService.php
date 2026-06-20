<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wiki;
use App\Repositories\WikiRepository;
use App\Support\Cache;
use Throwable;

/**
 * Knowledge-base use cases (Task I). The glossary index is cached on the
 * filesystem so the listing query runs at most once per cache window. Every
 * read is defensive: if the legacy `wiki_terms` table is missing the service
 * returns an empty list / null instead of throwing, so the page never breaks
 * (mirrors the StoryService resilience pattern).
 */
final class WikiService
{
    private const CACHE_TTL = 300; // seconds
    private const CACHE_KEY = 'wiki:list';

    public function __construct(
        private WikiRepository $wiki,
        private Cache $cache,
    ) {
    }

    /**
     * @return array<int, Wiki>
     */
    public function list(): array
    {
        try {
            return $this->cache->remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                fn (): array => $this->wiki->publishedList(),
            );
        } catch (Throwable) {
            return [];
        }
    }

    public function find(string $slug): ?Wiki
    {
        try {
            return $this->wiki->findBySlug($slug);
        } catch (Throwable) {
            return null;
        }
    }
}
