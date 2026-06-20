<?php
/**
 * @var string $q
 * @var array<int, \App\Models\Article> $articles
 * @var int    $page
 * @var int    $totalPages
 */
?>
<section class="listing">
    <header class="listing__head">
        <span class="listing__eyebrow">جستجو</span>
        <h1 class="listing__title">جستجو در مقاله‌ها</h1>
        <form class="search-form" method="get" action="/search" role="search">
            <input
                class="field__input search-form__input"
                type="search"
                name="q"
                value="<?= e($q) ?>"
                placeholder="عبارت مورد نظر را بنویسید…"
                aria-label="عبارت جستجو"
                autocomplete="off"
                <?= $q === '' ? 'autofocus' : '' ?>>
            <button class="btn btn-primary" type="submit">جستجو</button>
        </form>
    </header>

    <?php if ($q !== ''): ?>
        <p class="listing__summary">نتایج جستجو برای «<?= e($q) ?>»</p>
        <?php $emptyText = 'نتیجه‌ای برای این عبارت پیدا نشد.'; ?>
        <?php require __DIR__ . '/partials/feed-grid.php'; ?>
        <?php $baseUrl = '/search?q=' . rawurlencode($q); ?>
        <?php require __DIR__ . '/partials/pagination.php'; ?>
    <?php else: ?>
        <div class="empty-state card">
            <p>برای شروع، عبارتی را در کادر بالا جستجو کنید.</p>
        </div>
    <?php endif; ?>
</section>
