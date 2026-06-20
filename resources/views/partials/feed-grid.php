<?php
/**
 * Shared article grid. Expects:
 * @var array<int, \App\Models\Article> $articles
 * @var string $emptyText  (optional) message when the list is empty
 */
?>
<?php if ($articles === []): ?>
    <div class="empty-state card">
        <p><?= e($emptyText ?? 'چیزی یافت نشد.') ?></p>
    </div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($articles as $article): ?>
            <article class="card article-card">
                <div class="article-card__body">
                    <?php if ($article->categoryName && $article->categoryId): ?>
                        <a class="badge" href="/category/<?= (int) $article->categoryId ?>"><?= e($article->categoryName) ?></a>
                    <?php elseif ($article->categoryName): ?>
                        <span class="badge"><?= e($article->categoryName) ?></span>
                    <?php endif; ?>
                    <h2 class="article-card__title">
                        <a href="/<?= e(rawurlencode($article->title)) ?>"><?= e($article->title) ?></a>
                    </h2>
                    <?php if ($article->excerpt): ?>
                        <p class="article-card__excerpt"><?= e(mb_substr($article->excerpt, 0, 140)) ?></p>
                    <?php endif; ?>
                    <div class="article-card__meta">
                        <?php if ($article->userId): ?>
                            <a href="/author/<?= (int) $article->userId ?>"><?= e($article->authorName ?? 'ناشناس') ?></a>
                        <?php else: ?>
                            <span><?= e($article->authorName ?? 'ناشناس') ?></span>
                        <?php endif; ?>
                        <span class="dot">•</span>
                        <span><?= e(number_format($article->realReads)) ?> بازدید</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
