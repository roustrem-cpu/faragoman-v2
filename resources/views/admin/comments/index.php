<?php
/**
 * Admin comment moderation — list / approve / reject / delete.
 *
 * @var array<int, \App\Models\Comment> $comments
 * @var string      $filter
 * @var int         $total
 * @var int         $pendingCount
 * @var int         $page
 * @var int         $totalPages
 * @var string|null $flash
 */
$statusLabels = [
    'pending'  => 'در انتظار',
    'approved' => 'تأییدشده',
];
$tabs = [
    'pending'  => 'در انتظار',
    'approved' => 'تأییدشده',
    'all'      => 'همه',
];
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <p class="admin-toolbar__count">
            <?= e(number_format($total)) ?> دیدگاه<?= $filter !== 'all' ? ' (' . e($tabs[$filter] ?? '') . ')' : '' ?>
        </p>
        <nav class="comment-filters" aria-label="پالایش دیدگاه‌ها">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="/admin/comments?status=<?= e($key) ?>"
                   class="comment-filters__tab <?= $filter === $key ? 'is-active' : '' ?>"
                   <?= $filter === $key ? 'aria-current="page"' : '' ?>>
                    <?= e($label) ?><?php if ($key === 'pending' && $pendingCount > 0): ?> <span class="comment-filters__badge"><?= e(number_format($pendingCount)) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if ($comments === []): ?>
        <div class="empty-state card"><p>دیدگاهی برای نمایش وجود ندارد.</p></div>
    <?php else: ?>
        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>متن دیدگاه</th>
                        <th>نویسنده</th>
                        <th>مقاله</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th class="admin-table__actions-h">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td class="comment-cell"><?= e(mb_strimwidth($c->comment, 0, 140, '…')) ?></td>
                            <td>
                                <?= e($c->authorName()) ?>
                                <?php if ($c->isGuest()): ?><span class="badge">مهمان</span><?php endif; ?>
                            </td>
                            <td class="admin-table__title">
                                <?php if ($c->articleTitle !== null && $c->articleTitle !== ''): ?>
                                    <a href="/<?= e(rawurlencode($c->articleTitle)) ?>" target="_blank" rel="noopener"><?= e($c->articleTitle) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="tag tag--<?= e($c->status) ?>"><?= e($statusLabels[$c->status] ?? $c->status) ?></span></td>
                            <td dir="ltr" class="user-date"><?= e($c->createdAt !== null ? substr((string) $c->createdAt, 0, 10) : '—') ?></td>
                            <td>
                                <div class="admin-actions">
                                    <?php if ($c->status !== 'approved'): ?>
                                        <form method="post" action="/admin/comments/<?= (int) $c->id ?>/approve">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-primary btn-sm" type="submit">تأیید</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="/admin/comments/<?= (int) $c->id ?>/reject">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-sm" type="submit">لغو انتشار</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="/admin/comments/<?= (int) $c->id ?>/delete" onsubmit="return confirm('آیا از حذف این دیدگاه مطمئن هستید؟ این عمل بازگشت‌پذیر نیست.');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php $baseUrl = '/admin/comments?status=' . rawurlencode($filter); require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
</section>
