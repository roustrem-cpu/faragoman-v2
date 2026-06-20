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

    /**
     * Total number of published articles (cached scalar). Used by the admin
     * dashboard. Shares the home-count cache key to avoid a duplicate query.
     */
    public function publishedCount(): int
    {
        return $this->cache->remember(
            'feed:home:count',
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->countPublished(),
        );
    }

    public function findByTitle(string $title): ?Article
    {
        return $this->articles->findByTitle($title);
    }

    public function find(int $id): ?Article
    {
        return $this->articles->find($id);
    }

    // ----------------------------------------------------------------------
    // Category / Author / Search listings (Phase 3).
    //
    // NOTE: only the scalar COUNT is cached (ints serialize safely). The
    // article object lists are intentionally fetched fresh: the shared Cache
    // unserializes with `allowed_classes => false`, which would corrupt cached
    // model instances. Mirrors totalPages(), which already caches only ints.
    // ----------------------------------------------------------------------

    /**
     * @return array<int, Article>
     */
    public function categoryFeed(int $categoryId, int $page = 1, int $perPage = 12): array
    {
        return $this->articles->byCategory($categoryId, $perPage, $this->offset($page, $perPage));
    }

    public function categoryTotalPages(int $categoryId, int $perPage = 12): int
    {
        $total = $this->cache->remember(
            "feed:category:{$categoryId}:count",
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->countByCategory($categoryId),
        );

        return $this->pageCount($total, $perPage);
    }

    public function categoryName(int $categoryId): ?string
    {
        return $this->articles->findCategoryName($categoryId);
    }

    /**
     * @return array<int, Article>
     */
    public function authorFeed(int $userId, int $page = 1, int $perPage = 12): array
    {
        return $this->articles->byAuthor($userId, $perPage, $this->offset($page, $perPage));
    }

    public function authorTotalPages(int $userId, int $perPage = 12): int
    {
        $total = $this->cache->remember(
            "feed:author:{$userId}:count",
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->countByAuthor($userId),
        );

        return $this->pageCount($total, $perPage);
    }

    public function authorName(int $userId): ?string
    {
        return $this->articles->findAuthorName($userId);
    }

    /**
     * @return array<int, Article>
     */
    public function searchResults(string $term, int $page = 1, int $perPage = 12): array
    {
        if (trim($term) === '') {
            return [];
        }

        return $this->articles->search($term, $perPage, $this->offset($page, $perPage));
    }

    public function searchTotalPages(string $term, int $perPage = 12): int
    {
        if (trim($term) === '') {
            return 1;
        }

        $total = $this->cache->remember(
            'feed:search:' . md5($term) . ':count',
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->countSearch($term),
        );

        return $this->pageCount($total, $perPage);
    }

    // ----------------------------------------------------------------------
    // Admin management (Task D). Listings read all statuses; writes invalidate
    // the filesystem cache so public listings/counts update immediately.
    // ----------------------------------------------------------------------

    /**
     * @return array<int, Article>
     */
    public function adminList(int $page = 1, int $perPage = 20): array
    {
        return $this->articles->adminList($perPage, $this->offset($page, $perPage));
    }

    public function adminTotalPages(int $perPage = 20): int
    {
        $total = $this->cache->remember(
            'feed:admin:count',
            self::FEED_CACHE_TTL,
            fn (): int => $this->articles->adminCount(),
        );

        return $this->pageCount($total, $perPage);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function categories(): array
    {
        return $this->articles->allCategories();
    }

    /**
     * @param array{category_id:int,title:string,post_tag:string,content:string,excerpt:string,image_url:string,status:string} $data
     */
    public function createArticle(array $data, int $userId, string $authorName): int
    {
        $id = $this->articles->create([
            'user_id'     => $userId,
            'author_name' => $authorName,
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'post_tag'    => $data['post_tag'],
            'content'     => $data['content'],
            'excerpt'     => $data['excerpt'],
            'image_url'   => $data['image_url'],
            'status'      => $data['status'],
        ]);

        $this->cache->flush();

        return $id;
    }

    /**
     * @param array{category_id:int,title:string,post_tag:string,content:string,excerpt:string,image_url:string} $data
     */
    public function updateArticle(int $id, array $data): void
    {
        $this->articles->update($id, [
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'post_tag'    => $data['post_tag'],
            'content'     => $data['content'],
            'excerpt'     => $data['excerpt'],
            'image_url'   => $data['image_url'],
        ]);

        $this->cache->flush();
    }

    public function deleteArticle(int $id): void
    {
        $this->articles->delete($id);
        $this->cache->flush();
    }

    public function setStatus(int $id, string $status): void
    {
        $this->articles->setStatus($id, $status);
        $this->cache->flush();
    }

    private function offset(int $page, int $perPage): int
    {
        return (max(1, $page) - 1) * $perPage;
    }

    private function pageCount(int $total, int $perPage): int
    {
        return (int) max(1, (int) ceil($total / $perPage));
    }
}
