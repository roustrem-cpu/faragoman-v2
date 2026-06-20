<?php /** @var string|null $message */ /** @var string|null $trace */ ?>
<section class="error-page">
    <div class="error-card card">
        <h1 class="error-code">۵۰۰</h1>
        <p class="error-text">خطایی رخ داد. لطفاً بعداً دوباره تلاش کنید.</p>
        <?php if (!empty($message)): ?>
            <pre class="error-trace"><?= e($message) ?><?= $trace ? "\n\n" . e($trace) : '' ?></pre>
        <?php endif; ?>
        <a href="/" class="btn btn-primary">بازگشت به خانه</a>
    </div>
</section>
