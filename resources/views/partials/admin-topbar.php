<?php
/**
 * Admin topbar.
 * @var string $heading
 * @var \App\Models\User|null $currentUser
 */
$currentUser = $currentUser ?? null;
?>
<header class="admin-topbar">
    <h1 class="admin-topbar__title"><?= e($heading ?? 'مدیریت') ?></h1>

    <div class="admin-topbar__actions">
        <a href="/" class="btn btn-ghost">مشاهده سایت</a>
        <?php if ($currentUser !== null): ?>
            <span class="avatar-chip">
                <span class="avatar-chip__name"><?= e($currentUser->name()) ?></span>
            </span>
            <form method="post" action="/logout" class="inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost">خروج</button>
            </form>
        <?php endif; ?>
    </div>
</header>
