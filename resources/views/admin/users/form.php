<?php
/**
 * Edit user profile (admin).
 * @var \App\Models\User $user
 * @var string $role        normalised role slug
 * @var string $formAction
 * @var array<string, mixed>  $old
 * @var array<string, string> $errors
 */
$displayName = (string) ($old['display_name'] ?? ($user->displayName ?? ''));
$email       = (string) ($old['email']        ?? $user->email);
$userTitle   = (string) ($old['user_title']   ?? ($user->userTitle ?? ''));
$userBio     = (string) ($old['user_bio']     ?? ($user->userBio ?? ''));
$avatarUrl   = (string) ($old['avatar_url']   ?? ($user->avatarUrl ?? ''));
?>
<section class="admin-section admin-formwrap">
    <div class="card user-identity">
        <div class="user-identity__row">
            <span class="user-identity__label">نام کاربری</span>
            <code class="rbac-slug">@<?= e($user->username) ?></code>
        </div>
        <div class="user-identity__row">
            <span class="user-identity__label">نقش فعلی</span>
            <span>
                <code class="rbac-slug"><?= e($role) ?></code>
                <a class="user-identity__link" href="/admin/roles/users">تغییر نقش در بخش دسترسی‌ها</a>
            </span>
        </div>
        <div class="user-identity__row">
            <span class="user-identity__label">وضعیت</span>
            <?php if ($user->isBanned): ?>
                <span class="tag tag--banned">مسدود</span>
            <?php else: ?>
                <span class="tag tag--active">فعال</span>
            <?php endif; ?>
        </div>
    </div>

    <form class="card form admin-form" method="post" action="<?= e($formAction) ?>">
        <?= csrf_field() ?>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="display_name">نام نمایشی</label>
                <input class="field__input" id="display_name" name="display_name" value="<?= e($displayName) ?>" placeholder="<?= e($user->username) ?>">
                <?php if (!empty($errors['display_name'])): ?><span class="field__error"><?= e($errors['display_name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="email">ایمیل</label>
                <input class="field__input" id="email" name="email" type="email" dir="ltr" value="<?= e($email) ?>" required>
                <?php if (!empty($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="user_title">عنوان کاربر</label>
                <input class="field__input" id="user_title" name="user_title" value="<?= e($userTitle) ?>" placeholder="مثلاً: نویسنده ارشد">
                <?php if (!empty($errors['user_title'])): ?><span class="field__error"><?= e($errors['user_title']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="avatar_url">نشانی تصویر پروفایل</label>
                <input class="field__input" id="avatar_url" name="avatar_url" dir="ltr" value="<?= e($avatarUrl) ?>" placeholder="uploads/avatars/…">
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="user_bio">معرفی</label>
            <textarea class="field__input" id="user_bio" name="user_bio" rows="4"><?= e($userBio) ?></textarea>
        </div>

        <div class="admin-form__actions">
            <a href="/admin/users" class="btn btn-ghost">انصراف</a>
            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
        </div>
    </form>
</section>
