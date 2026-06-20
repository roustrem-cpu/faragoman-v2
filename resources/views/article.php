<?php
/** @var \App\Models\Article $article */
?>
<article class="article">
    <a href="/" class="article__back">
        <span aria-hidden="true">→</span>
        <span>بازگشت به خانه</span>
    </a>

    <header class="article__head card">
        <?php if ($article->categoryName): ?>
            <span class="badge"><?= e($article->categoryName) ?></span>
        <?php endif; ?>

        <h1 class="article__title"><?= e($article->title) ?></h1>

        <div class="article__meta">
            <span class="article__author"><?= e($article->authorName ?? 'ناشناس') ?></span>
            <span class="dot">•</span>
            <span><?= e(number_format($article->realReads)) ?> بازدید</span>
            <?php if ($article->createdAt): ?>
                <span class="dot">•</span>
                <time datetime="<?= e($article->createdAt) ?>"><?= e($article->createdAt) ?></time>
            <?php endif; ?>
        </div>

        <?php if ($article->excerpt): ?>
            <p class="article__excerpt"><?= e($article->excerpt) ?></p>
        <?php endif; ?>
    </header>

    <div class="article__body card">
        <?php if ($article->content !== null && trim($article->content) !== ''): ?>
            <?php /* Article bodies are author-managed rich HTML (legacy behaviour); rendered as-is. */ ?>
            <?= $article->content ?>
        <?php else: ?>
            <p class="article__empty">محتوایی برای نمایش وجود ندارد.</p>
        <?php endif; ?>
    </div>
</article>
