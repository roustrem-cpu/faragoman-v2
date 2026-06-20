<?php
/**
 * Shared pagination. Expects:
 * @var int    $page
 * @var int    $totalPages
 * @var string $baseUrl     path (may already contain a query string)
 */
$baseUrl = $baseUrl ?? '/';
?>
<?php if (($totalPages ?? 1) > 1): ?>
    <?php $sep = str_contains($baseUrl, '?') ? '&' : '?'; ?>
    <nav class="pagination" aria-label="صفحه‌بندی">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= e($baseUrl . $sep . 'page_num=' . $i) ?>" class="page-pill <?= $i === $page ? 'is-active' : '' ?>"><?= e($i) ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
