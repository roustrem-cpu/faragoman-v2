<?php
/**
 * Per-role permission editor (matrix grouped by category).
 * @var \App\Models\Role $role
 * @var bool             $isSuperAdmin
 * @var array<string, array<int, \App\Models\Permission>> $groups
 * @var array<int, int>     $selected     granted permission ids
 * @var array<string,string> $categories  category slug => label
 */
$selectedMap = array_fill_keys(array_map('intval', $selected), true);
?>
<section class="admin-section admin-formwrap admin-formwrap--wide">
    <div class="admin-toolbar">
        <p class="admin-toolbar__count">
            شناسه نقش: <code class="rbac-slug"><?= e($role->slug) ?></code> · رتبه <?= e(number_format($role->rank)) ?>
        </p>
        <a href="/admin/roles" class="btn btn-ghost btn-sm">بازگشت</a>
    </div>

    <?php if ($isSuperAdmin): ?>
        <div class="card rbac-notice">
            <h2 class="rbac-notice__title">مدیر کل به همه‌ی دسترسی‌ها دسترسی دارد</h2>
            <p class="rbac-notice__body">
                نقش «مدیر کل» به‌صورت ضمنی تمام دسترسی‌ها را دارد و از همه‌ی بررسی‌ها عبور می‌کند؛
                بنابراین ویرایش دسترسی‌های آن لازم نیست.
            </p>
        </div>
    <?php else: ?>
        <form class="card form admin-form" method="post" action="/admin/roles/<?= (int) $role->id ?>/permissions">
            <?= csrf_field() ?>

            <?php foreach ($groups as $category => $permissions): ?>
                <fieldset class="perm-group">
                    <legend class="perm-group__title"><?= e($categories[$category] ?? $category) ?></legend>
                    <div class="perm-grid">
                        <?php foreach ($permissions as $perm): ?>
                            <label class="perm-check">
                                <input type="checkbox" name="permissions[]" value="<?= (int) $perm->id ?>"
                                    <?= isset($selectedMap[$perm->id]) ? 'checked' : '' ?>>
                                <span class="perm-check__text">
                                    <span class="perm-check__name"><?= e($perm->name) ?></span>
                                    <code class="perm-check__slug"><?= e($perm->slug) ?></code>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div class="admin-form__actions">
                <a href="/admin/roles" class="btn btn-ghost">انصراف</a>
                <button type="submit" class="btn btn-primary">ذخیره دسترسی‌ها</button>
            </div>
        </form>
    <?php endif; ?>
</section>
