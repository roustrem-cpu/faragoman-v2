<?php /** @var string|null $error */ ?>
<section class="auth-wrap">
    <div class="auth-card card">
        <div class="auth-card__head">
            <span class="brand-mark brand-mark--lg">ف</span>
            <h1>ورود به فراگمان</h1>
            <p>برای ادامه وارد حساب کاربری خود شوید.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" class="form">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field__label">نام کاربری یا ایمیل</span>
                <input class="field__input" type="text" name="login" value="<?= old('login') ?>" required autofocus autocomplete="username">
            </label>
            <label class="field">
                <span class="field__label">رمز عبور</span>
                <input class="field__input" type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn btn-primary btn-block">ورود</button>
        </form>

        <div class="auth-card__foot">
            <a href="/forgot-password">رمز عبور را فراموش کرده‌اید؟</a>
        </div>
    </div>
</section>
