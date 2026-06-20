<?php
/**
 * Admin shell layout.
 * @var string      $content
 * @var string      $title
 * @var string      $heading
 * @var string      $activeNav
 * @var \App\Models\User|null $currentUser
 */
$activeNav = $activeNav ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'مدیریت — فراگمان') ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.min.css') ?>">
    <link rel="icon" href="<?= asset('img/favicon.png') ?>">
</head>
<body class="page admin-body">
    <div class="aurora" aria-hidden="true"></div>

    <div class="admin-shell">
        <?php require dirname(__DIR__) . '/partials/admin-sidebar.php'; ?>

        <div class="admin-main">
            <?php require dirname(__DIR__) . '/partials/admin-topbar.php'; ?>

            <main class="admin-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
