<?php
/**
 * Knowledge-base / glossary index (Task I).
 *
 * @var array<int, \App\Models\Wiki> $terms
 */
?>
<section class="listing wiki-index">
    <header class="listing__head">
        <span class="listing__eyebrow">دانشنامه</span>
        <h1 class="listing__title">واژه‌نامه و دانشنامه</h1>
    </header>

    <?php if ($terms === []): ?>
        <div class="empty-state card"><p>هنوز مدخلی در دانشنامه ثبت نشده است.</p></div>
    <?php else: ?>
        <div class="card-grid wiki-grid">
            <?php foreach ($terms as $t): ?>
                <article class="card wiki-card">
                    <h2 class="wiki-card__term">
                        <?php if ($t->isFullPage()): ?>
                            <a href="/wiki/<?= e(rawurlencode($t->slug)) ?>"><?= e($t->term) ?></a>
                        <?php else: ?>
                            <?= e($t->term) ?>
                        <?php endif; ?>
                    </h2>
                    <?php if ($t->briefDesc !== ''): ?>
                        <p class="wiki-card__brief"><?= e(mb_substr($t->briefDesc, 0, 160)) ?></p>
                    <?php endif; ?>
                    <?php if ($t->isFullPage()): ?>
                        <a class="wiki-card__more" href="/wiki/<?= e(rawurlencode($t->slug)) ?>">ادامه ←</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
