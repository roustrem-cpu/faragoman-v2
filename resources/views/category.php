<?php
/**
 * @var int         $categoryId
 * @var string|null $categoryName
 * @var array<int, \App\Models\Article> $articles
 * @var int         $page
 * @var int         $totalPages
 */
?>
<section class="listing">
    <header class="listing__head">
        <span class="listing__eyebrow">دسته‌بندی</span>
        <h1 class="listing__title"><?= e($categoryName ?? 'دسته‌بندی') ?></h1>
    </header>

    <?php $emptyText = 'هنوز مقاله‌ای در این دسته‌بندی منتشر نشده است.'; ?>
    <?php require __DIR__ . '/partials/feed-grid.php'; ?>

    <?php $baseUrl = '/category/' . (int) $categoryId; ?>
    <?php require __DIR__ . '/partials/pagination.php'; ?>
</section>
