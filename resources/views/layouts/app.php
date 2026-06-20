<?php /** @var string $content */ /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1020">
    <title><?= e($title ?? 'فراگمان') ?></title>
    <meta name="description" content="فراگمان — مجلهٔ محتوای فارسی">
    <link rel="stylesheet" href="<?= asset('css/app.min.css') ?>">
    <link rel="icon" href="<?= asset('img/favicon.png') ?>">
</head>
<body class="page">
    <div class="aurora" aria-hidden="true"></div>

    <?php require dirname(__DIR__) . '/partials/header.php'; ?>

    <main class="container main">
        <?= $content ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
