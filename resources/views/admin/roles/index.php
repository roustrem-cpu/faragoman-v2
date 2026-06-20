<?php
/**
 * RBAC overview — roles list + permission summary.
 * @var bool                       $ready
 * @var array<int, \App\Models\Role> $roles
 * @var array<int, int>            $counts    role id => permission count
 * @var int                        $permTotal
 * @var string|null                $flash
 */
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (!$ready): ?>
        <div class="card rbac-notice">
            <h2 class="rbac-notice__title">جداول کنترل دسترسی هنوز ساخته نشده‌اند</h2>
            <p class="rbac-notice__body">
                این بخش به جدول‌های افزایشی <code>roles</code>، <code>permissions</code>،
                <code>role_permissions</code> و <code>user_permissions</code> نیاز دارد. این جدول‌ها
                کاملاً اختیاری و غیرمخرب هستند و هیچ جدول موجودی را تغییر نمی‌دهند.
            </p>
            <p class="rbac-notice__body">
                فایل <code>database/schema.sql</code> را یک‌بار از طریق phpMyAdmin (سربرگ Import) روی
                دیتابیس فعلی اجرا کنید. تا آن زمان، برنامه از یک ماتریس دسترسی پیش‌فرض داخلی استفاده می‌کند
                و دچار خطا نمی‌شود.
            </p>
        </div>
    <?php else: ?>
        <div class="admin-statgrid">
            <div class="card stat-card">
                <span class="stat-card__value"><?= e(number_format(count($roles))) ?></span>
                <span class="stat-card__label">نقش‌های تعریف‌شده</span>
            </div>
            <div class="card stat-card">
                <span class="stat-card__value"><?= e(number_format($permTotal)) ?></span>
                <span class="stat-card__label">دسترسی‌های موجود</span>
            </div>
        </div>

        <div class="admin-toolbar">
            <p class="admin-toolbar__count">مدیریت نقش‌ها، دسترسی‌ها و تخصیص آن‌ها به کاربران</p>
            <div class="admin-actions">
                <a href="/admin/roles/users" class="btn btn-ghost">تخصیص نقش به کاربران</a>
                <a href="/admin/roles/create" class="btn btn-primary">+ نقش جدید</a>
            </div>
        </div>

        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>نام نقش</th>
                        <th>شناسه</th>
                        <th>رتبه</th>
                        <th>دسترسی‌ها</th>
                        <th class="admin-table__actions-h">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <?php $isSuper = $role->slug === \App\Support\Rbac::SUPER_ADMIN; ?>
                        <tr>
                            <td class="admin-table__title"><?= e($role->name) ?></td>
                            <td><code class="rbac-slug"><?= e($role->slug) ?></code></td>
                            <td><span class="role-rank"><?= e(number_format($role->rank)) ?></span></td>
                            <td>
                                <?php if ($isSuper): ?>
                                    <span class="tag tag--published">همه دسترسی‌ها</span>
                                <?php else: ?>
                                    <?= e(number_format($counts[$role->id] ?? 0)) ?> از <?= e(number_format($permTotal)) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a class="btn btn-ghost btn-sm" href="/admin/roles/<?= (int) $role->id ?>/permissions">دسترسی‌ها</a>
                                    <a class="btn btn-ghost btn-sm" href="/admin/roles/<?= (int) $role->id ?>/edit">ویرایش</a>
                                    <?php if (!in_array($role->slug, [\App\Support\Rbac::SUPER_ADMIN, \App\Support\Rbac::SECTION_ADMIN, \App\Support\Rbac::EDITOR, \App\Support\Rbac::AUTHOR, \App\Support\Rbac::USER], true)): ?>
                                        <form method="post" action="/admin/roles/<?= (int) $role->id ?>/delete" onsubmit="return confirm('این نقش حذف شود؟ کاربران دارای این نقش به نقش پیش‌فرض بازمی‌گردند.');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger btn-sm" type="submit">حذف</button>
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
</section>
