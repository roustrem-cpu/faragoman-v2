<?php
/**
 * Per-user permission overrides (allow / deny / inherit).
 * @var \App\Models\User $targetUser
 * @var string $normalRole
 * @var bool   $isSuperAdmin
 * @var array<string, array<int, \App\Models\Permission>> $groups
 * @var array<int, string>   $overrides   permission id => allow|deny
 * @var array<string,string> $categories
 */
?>
<section class="admin-section admin-formwrap admin-formwrap--wide">
    <div class="admin-toolbar">
        <p class="admin-toolbar__count">
            کاربر: <strong><?= e($targetUser->name()) ?></strong> ·
            نقش: <code class="rbac-slug"><?= e($normalRole) ?></code>
        </p>
        <a href="/admin/roles/users" class="btn btn-ghost btn-sm">بازگشت</a>
    </div>

    <?php if ($isSuperAdmin): ?>
        <div class="card rbac-notice">
            <h2 class="rbac-notice__title">این کاربر مدیر کل است</h2>
            <p class="rbac-notice__body">
                مدیر کل از همه‌ی بررسی‌های دسترسی عبور می‌کند؛ بنابراین دسترسی‌های اختصاصی روی او اثری ندارند.
            </p>
        </div>
    <?php else: ?>
        <p class="rbac-hint card">
            «ارثی» یعنی دسترسی از روی نقش کاربر تعیین می‌شود. «مجاز» و «ممنوع» این تصمیم را برای همین کاربر
            بازنویسی می‌کنند («ممنوع» بر هر دسترسی نقش اولویت دارد).
        </p>

        <form class="card form admin-form" method="post" action="/admin/roles/users/<?= (int) $targetUser->id ?>/overrides">
            <?= csrf_field() ?>

            <?php foreach ($groups as $category => $permissions): ?>
                <fieldset class="perm-group">
                    <legend class="perm-group__title"><?= e($categories[$category] ?? $category) ?></legend>
                    <div class="override-list">
                        <?php foreach ($permissions as $perm): ?>
                            <?php $current = $overrides[$perm->id] ?? 'inherit'; ?>
                            <div class="override-row">
                                <span class="perm-check__text">
                                    <span class="perm-check__name"><?= e($perm->name) ?></span>
                                    <code class="perm-check__slug"><?= e($perm->slug) ?></code>
                                </span>
                                <div class="seg" role="group" aria-label="<?= e($perm->name) ?>">
                                    <?php
                                    $opts = ['inherit' => 'ارثی', 'allow' => 'مجاز', 'deny' => 'ممنوع'];
                                    foreach ($opts as $value => $label):
                                        $id = 'p' . (int) $perm->id . '_' . $value;
                                    ?>
                                        <input class="seg__radio seg__radio--<?= e($value) ?>" type="radio"
                                               id="<?= e($id) ?>" name="effect[<?= (int) $perm->id ?>]"
                                               value="<?= e($value) ?>" <?= $current === $value ? 'checked' : '' ?>>
                                        <label class="seg__label" for="<?= e($id) ?>"><?= e($label) ?></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div class="admin-form__actions">
                <a href="/admin/roles/users" class="btn btn-ghost">انصراف</a>
                <button type="submit" class="btn btn-primary">ذخیره دسترسی‌های اختصاصی</button>
            </div>
        </form>
    <?php endif; ?>
</section>
