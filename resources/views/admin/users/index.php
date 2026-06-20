<?php
/**
 * Admin user management — list + search.
 * @var array<int, \App\Models\User> $users
 * @var \App\Support\Rbac $engine
 * @var int    $selfId
 * @var string $search
 * @var int    $total
 * @var int    $page
 * @var int    $totalPages
 * @var string|null $flash
 */
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <p class="admin-toolbar__count"><?= e(number_format($total)) ?> کاربر<?= $search !== '' ? ' (نتیجهٔ جستجو)' : '' ?></p>
        <form method="get" action="/admin/users" class="user-search" role="search">
            <input class="field__input user-search__input" type="search" name="q"
                   value="<?= e($search) ?>" placeholder="جستجوی نام کاربری، ایمیل یا نام نمایشی…">
            <button type="submit" class="btn btn-primary btn-sm">جستجو</button>
            <?php if ($search !== ''): ?>
                <a href="/admin/users" class="btn btn-ghost btn-sm">پاک‌سازی</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($users === []): ?>
        <div class="empty-state card"><p>کاربری یافت نشد.</p></div>
    <?php else: ?>
        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>وضعیت</th>
                        <th>عضویت</th>
                        <th class="admin-table__actions-h">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php $isSelf = (int) $u->id === (int) $selfId; ?>
                        <tr>
                            <td class="admin-table__title">
                                <?= e($u->name()) ?>
                                <?php if ($isSelf): ?><span class="badge">شما</span><?php endif; ?>
                                <span class="rbac-username">@<?= e($u->username) ?></span>
                            </td>
                            <td dir="ltr" class="user-email"><?= e($u->email) ?></td>
                            <td><code class="rbac-slug"><?= e($engine->normaliseRole($u->role)) ?></code></td>
                            <td>
                                <?php if ($u->isBanned): ?>
                                    <span class="tag tag--banned">مسدود</span>
                                <?php else: ?>
                                    <span class="tag tag--active">فعال</span>
                                <?php endif; ?>
                            </td>
                            <td dir="ltr" class="user-date"><?= e($u->createdAt !== null ? substr((string) $u->createdAt, 0, 10) : '—') ?></td>
                            <td>
                                <div class="admin-actions">
                                    <a class="btn btn-ghost btn-sm" href="/admin/users/<?= (int) $u->id ?>/edit">ویرایش</a>
                                    <?php if ($u->isBanned): ?>
                                        <form method="post" action="/admin/users/<?= (int) $u->id ?>/unban">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-primary btn-sm" type="submit">رفع مسدودی</button>
                                        </form>
                                    <?php elseif (!$isSelf): ?>
                                        <form method="post" action="/admin/users/<?= (int) $u->id ?>/ban" onsubmit="return confirm('این کاربر مسدود شود؟ امکان ورود نخواهد داشت.');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger btn-sm" type="submit">مسدودسازی</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php $baseUrl = '/admin/users' . ($search !== '' ? '?q=' . rawurlencode($search) : ''); require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
</section>
