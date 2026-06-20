<?php
/**
 * @var int         $authorId
 * @var string|null $authorName
 * @var array<int, \App\Models\Article> $articles
 * @var int         $page
 * @var int         $totalPages
 */
?>
<section class="listing">
    <header class="listing__head">
        <span class="listing__eyebrow">نویسنده</span>
        <h1 class="listing__title"><?= e($authorName ?? 'نویسنده') ?></h1>
        <a class="listing__profile-link" href="/profile/<?= (int) $authorId ?>">مشاهده پروفایل کامل</a>
    </header>

    <?php $emptyText = 'این نویسنده هنوز مقاله‌ای منتشر نکرده است.'; ?>
    <?php require __DIR__ . '/partials/feed-grid.php'; ?>

    <?php $baseUrl = '/author/' . (int) $authorId; ?>
    <?php require __DIR__ . '/partials/pagination.php'; ?>
</section>
