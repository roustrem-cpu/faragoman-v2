<?php
/**
 * @var array<int, \App\Models\Article> $articles
 * @var int         $page
 * @var int         $totalPages
 * @var string|null $flash
 */
$statusLabels = [
    'published' => 'منتشر شده',
    'pending'   => 'در انتظار',
    'approved'  => 'تأییدشده',
    'rejected'  => 'رد شده',
];
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <p class="admin-toolbar__count"><?= e(number_format(count($articles))) ?> مورد در این صفحه</p>
        <a href="/admin/articles/create" class="btn btn-primary">+ مقاله جدید</a>
    </div>

    <?php if ($articles === []): ?>
        <div class="empty-state card"><p>هنوز مقاله‌ای ثبت نشده است.</p></div>
    <?php else: ?>
        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>نویسنده</th>
                        <th>دسته</th>
                        <th>وضعیت</th>
                        <th>بازدید</th>
                        <th class="admin-table__actions-h">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $a): ?>
                        <tr>
                            <td class="admin-table__title">
                                <a href="/<?= e(rawurlencode($a->title)) ?>" target="_blank" rel="noopener"><?= e($a->title) ?></a>
                            </td>
                            <td><?= e($a->authorName ?? '—') ?></td>
                            <td><?= e($a->categoryName ?? '—') ?></td>
                            <td><span class="tag tag--<?= e($a->status) ?>"><?= e($statusLabels[$a->status] ?? $a->status) ?></span></td>
                            <td><?= e(number_format($a->realReads)) ?></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="btn btn-ghost btn-sm" href="/admin/articles/<?= (int) $a->id ?>/edit">ویرایش</a>
                                    <?php if ($a->status === 'published'): ?>
                                        <form method="post" action="/admin/articles/<?= (int) $a->id ?>/unpublish">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-sm" type="submit">لغو انتشار</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="/admin/articles/<?= (int) $a->id ?>/publish">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-primary btn-sm" type="submit">انتشار</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="/admin/articles/<?= (int) $a->id ?>/delete" onsubmit="return confirm('این مقاله حذف شود؟ این عمل بازگشت‌پذیر نیست.');">
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

    <?php $baseUrl = '/admin/articles'; require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
</section>
