<?php /** @var \App\Models\User|null $currentUser */ $currentUser = $currentUser ?? null; ?>
<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand" aria-label="فراگمان">
            <span class="brand-mark">ف</span>
            <span class="brand-name">فراگمان</span>
        </a>

        <nav class="nav" aria-label="ناوبری اصلی">
            <a href="/" class="nav-link is-active">خانه</a>
            <a href="/search" class="nav-link">جستجو</a>
            <a href="/wiki" class="nav-link">ویکی</a>
            <a href="/store" class="nav-link">فروشگاه</a>
            <a href="/chat" class="nav-link">گفتگو</a>
        </nav>

        <div class="header-actions">
            <?php if ($currentUser !== null): ?>
                <a href="/profile" class="avatar-chip">
                    <span class="avatar-chip__name"><?= e($currentUser->name()) ?></span>
                </a>
                <form method="post" action="/logout" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost">خروج</button>
                </form>
            <?php else: ?>
                <a href="/login" class="btn btn-primary">ورود</a>
            <?php endif; ?>
        </div>
    </div>
</header>
