<?php
/** @var array<int, \App\Models\Story> $stories */
$stories = $stories ?? [];
if ($stories === []) {
    return;
}
?>
<section class="stories" aria-label="استوری‌ها">
    <div class="stories-rail" role="list">
        <?php foreach ($stories as $i => $story): ?>
            <button
                type="button"
                class="story-ring"
                role="listitem"
                data-story-index="<?= (int) $i ?>"
                data-story-title="<?= e($story->title) ?>"
                data-story-image="<?= e($story->imageUrl) ?>"
                data-story-link="<?= e($story->linkUrl ?? '') ?>"
                aria-label="<?= e($story->title) ?>"
            >
                <span class="story-ring__halo" aria-hidden="true"></span>
                <span class="story-ring__frame">
                    <img class="story-ring__img" src="<?= e($story->imageUrl) ?>" alt="" loading="lazy" decoding="async">
                </span>
                <span class="story-ring__label"><?= e(mb_strimwidth($story->title, 0, 18, '…')) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<div id="story-viewer" class="story-viewer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="نمایش استوری">
    <div class="story-viewer__backdrop" data-story-close></div>
    <div class="story-viewer__stage">
        <div class="story-viewer__bars" id="story-bars" aria-hidden="true"></div>
        <header class="story-viewer__head">
            <span class="story-viewer__title" id="story-viewer-title"></span>
            <button type="button" class="story-viewer__close" data-story-close aria-label="بستن">&times;</button>
        </header>
        <img class="story-viewer__img" id="story-viewer-img" src="" alt="">
        <a class="story-viewer__cta" id="story-viewer-cta" href="#" rel="noopener" hidden>مشاهده</a>
        <button type="button" class="story-viewer__nav story-viewer__nav--prev" id="story-prev" aria-label="قبلی"></button>
        <button type="button" class="story-viewer__nav story-viewer__nav--next" id="story-next" aria-label="بعدی"></button>
    </div>
</div>
