<?php
/**
 * Admin sidebar navigation.
 * @var string $activeNav
 */
$activeNav = $activeNav ?? 'dashboard';

$items = [
    ['key' => 'dashboard', 'href' => '/admin',          'label' => 'داشبورد'],
    ['key' => 'articles',  'href' => '/admin/articles', 'label' => 'مقاله‌ها'],
    ['key' => 'users',     'href' => '/admin/users',    'label' => 'کاربران'],
    ['key' => 'comments',  'href' => '/admin/comments', 'label' => 'دیدگاه‌ها'],
    ['key' => 'stories',   'href' => '/admin/stories',  'label' => 'داستان‌ها'],
    ['key' => 'roles',     'href' => '/admin/roles',    'label' => 'نقش‌ها و دسترسی'],
];
?>
<aside class="admin-sidebar">
    <a href="/admin" class="admin-sidebar__brand">
        <span class="brand-mark">ف</span>
        <span class="admin-sidebar__brandtext">
            <span class="admin-sidebar__brandname">فراگمان</span>
            <span class="admin-sidebar__brandsub">پنل مدیریت</span>
        </span>
    </a>

    <nav class="admin-nav" aria-label="ناوبری مدیریت">
        <?php foreach ($items as $item): ?>
            <a
                href="<?= e($item['href']) ?>"
                class="admin-nav__link <?= $activeNav === $item['key'] ? 'is-active' : '' ?>"
                <?= $activeNav === $item['key'] ? 'aria-current="page"' : '' ?>>
                <span class="admin-nav__dot" aria-hidden="true"></span>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <a href="/" class="admin-nav__link admin-sidebar__back">
        <span class="admin-nav__dot" aria-hidden="true"></span>
        <span>بازگشت به سایت</span>
    </a>
</aside>
