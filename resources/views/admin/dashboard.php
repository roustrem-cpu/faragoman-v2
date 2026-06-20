<?php
/**
 * @var int $publishedCount
 */
$sections = [
    ['href' => '/admin/articles', 'title' => 'مدیریت مقاله‌ها', 'desc' => 'ایجاد، ویرایش و انتشار مقاله‌ها'],
    ['href' => '/admin/users',    'title' => 'کاربران',         'desc' => 'مدیریت حساب‌ها و وضعیت کاربران'],
    ['href' => '/admin/comments', 'title' => 'دیدگاه‌ها',       'desc' => 'بررسی و مدیریت دیدگاه‌ها'],
    ['href' => '/admin/stories',  'title' => 'داستان‌ها',       'desc' => 'مدیریت استوری‌های صفحه‌ی اصلی'],
    ['href' => '/admin/roles',    'title' => 'نقش‌ها و دسترسی', 'desc' => 'تخصیص دسترسی‌ها به نقش‌ها و کاربران'],
];
?>
<section class="admin-section">
    <div class="admin-statgrid">
        <div class="card stat-card">
            <span class="stat-card__value"><?= e(number_format($publishedCount ?? 0)) ?></span>
            <span class="stat-card__label">مقاله‌های منتشرشده</span>
        </div>
    </div>

    <h2 class="admin-section__title">مدیریت بخش‌ها</h2>
    <div class="admin-cards">
        <?php foreach ($sections as $s): ?>
            <a class="card admin-card" href="<?= e($s['href']) ?>">
                <span class="admin-card__title"><?= e($s['title']) ?></span>
                <span class="admin-card__desc"><?= e($s['desc']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
