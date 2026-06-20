<?php
/**
 * Public user profile (Task I).
 *
 * @var \App\Models\User              $profileUser
 * @var array<int, \App\Models\Article> $articles
 * @var int    $page
 * @var int    $totalPages
 * @var string $baseUrl
 */
?>
<section class="profile">
    <header class="profile__head card">
        <div class="profile__avatar">
            <?php if ($profileUser->avatarUrl !== null && $profileUser->avatarUrl !== ''): ?>
                <img class="profile__avatar-img" src="<?= e($profileUser->avatarUrl) ?>" alt="" loading="lazy">
            <?php else: ?>
                <span class="profile__avatar-mark"><?= e(mb_substr($profileUser->name(), 0, 1)) ?></span>
            <?php endif; ?>
        </div>
        <div class="profile__meta">
            <h1 class="profile__name"><?= e($profileUser->name()) ?></h1>
            <p class="profile__username" dir="ltr">@<?= e($profileUser->username) ?></p>
            <?php if ($profileUser->userTitle !== null && $profileUser->userTitle !== ''): ?>
                <p class="profile__title"><?= e($profileUser->userTitle) ?></p>
            <?php endif; ?>
            <?php if ($profileUser->createdAt !== null): ?>
                <p class="profile__joined">عضویت از <?= e(substr((string) $profileUser->createdAt, 0, 10)) ?></p>
            <?php endif; ?>
            <a class="profile__archive" href="/author/<?= (int) $profileUser->id ?>">آرشیو نوشته‌ها</a>
        </div>
    </header>

    <?php if ($profileUser->userBio !== null && $profileUser->userBio !== ''): ?>
        <div class="profile__bio card"><?= nl2br(e($profileUser->userBio)) ?></div>
    <?php endif; ?>

    <h2 class="profile__section-title">نوشته‌ها</h2>
    <?php $emptyText = 'این کاربر هنوز نوشتهٔ منتشرشده‌ای ندارد.'; ?>
    <?php require __DIR__ . '/partials/feed-grid.php'; ?>
    <?php require __DIR__ . '/partials/pagination.php'; ?>
</section>
