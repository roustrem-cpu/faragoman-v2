<?php
/**
 * Single knowledge-base entry (Task I).
 *
 * @var \App\Models\Wiki $term
 */
$hasFull = $term->fullContent !== null && $term->fullContent !== '';
?>
<section class="wiki-entry">
    <div class="wiki-entry__back"><a href="/wiki">← بازگشت به دانشنامه</a></div>

    <article class="card wiki-entry__card">
        <header class="wiki-entry__head">
            <h1 class="wiki-entry__term"><?= e($term->term) ?></h1>
            <?php if ($term->briefDesc !== ''): ?>
                <p class="wiki-entry__brief"><?= e($term->briefDesc) ?></p>
            <?php endif; ?>
        </header>

        <div class="wiki-entry__body">
            <?php if ($hasFull): ?>
                <?php /* Author-managed rich HTML, echoed raw — same convention as article bodies. */ ?>
                <?= $term->fullContent ?>
            <?php else: ?>
                <p><?= nl2br(e($term->briefDesc)) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($term->updatedAt !== null): ?>
            <footer class="wiki-entry__foot" dir="ltr">به‌روزرسانی: <?= e(substr((string) $term->updatedAt, 0, 10)) ?></footer>
        <?php endif; ?>
    </article>
</section>
