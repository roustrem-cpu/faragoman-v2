<?php
/** @var array<int, \App\Models\Article> $articles */
/** @var int $page */
/** @var int $totalPages */
?>
<section class="hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <h1 class="hero-title">روایت‌هایی که می‌مانند</h1>
    <p class="hero-sub">جدیدترین مقاله‌ها و داستان‌های فراگمان، با تجربه‌ای تازه و سریع.</p>
</section>

<section class="feed" aria-label="آخرین مقاله‌ها">
    <?php if ($articles === []): ?>
        <div class="empty-state card">
            <p>هنوز مقاله‌ای منتشر نشده است.</p>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($articles as $article): ?>
                <article class="card article-card">
                    <div class="article-card__body">
                        <?php if ($article->categoryName): ?>
                            <span class="badge"><?= e($article->categoryName) ?></span>
                        <?php endif; ?>
                        <h2 class="article-card__title">
                            <a href="/<?= e(rawurlencode($article->title)) ?>"><?= e($article->title) ?></a>
                        </h2>
                        <?php if ($article->excerpt): ?>
                            <p class="article-card__excerpt"><?= e(mb_substr($article->excerpt, 0, 140)) ?></p>
                        <?php endif; ?>
                        <div class="article-card__meta">
                            <span><?= e($article->authorName ?? 'ناشناس') ?></span>
                            <span class="dot">•</span>
                            <span><?= e(number_format($article->realReads)) ?> بازدید</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="صفحه‌بندی">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/?page_num=<?= $i ?>" class="page-pill <?= $i === $page ? 'is-active' : '' ?>"><?= e($i) ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</section>
