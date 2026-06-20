<?php
/**
 * Assign roles to users.
 * @var array<int, \App\Models\User> $users
 * @var array<int, \App\Models\Role> $roles
 * @var \App\Support\Rbac $engine
 * @var int    $selfId
 * @var int    $page
 * @var int    $totalPages
 * @var string|null $flash
 */
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <p class="admin-toolbar__count">نقش هر کاربر را انتخاب کنید؛ تغییر بلافاصله اعمال می‌شود.</p>
        <a href="/admin/roles" class="btn btn-ghost btn-sm">بازگشت به نقش‌ها</a>
    </div>

    <?php if ($users === []): ?>
        <div class="empty-state card"><p>کاربری یافت نشد.</p></div>
    <?php else: ?>
        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>نقش فعلی</th>
                        <th>تخصیص نقش</th>
                        <th class="admin-table__actions-h">دسترسی اختصاصی</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                            $normal = $engine->normaliseRole($u->role);
                            $isSelf = (int) $u->id === (int) $selfId;
                        ?>
                        <tr>
                            <td class="admin-table__title">
                                <?= e($u->name()) ?>
                                <?php if ($isSelf): ?><span class="badge">شما</span><?php endif; ?>
                                <span class="rbac-username">@<?= e($u->username) ?></span>
                            </td>
                            <td>
                                <code class="rbac-slug"><?= e($normal) ?></code>
                                <?php if (strtolower($u->role) !== $normal): ?>
                                    <span class="rbac-rawrole">(ذخیره‌شده: <?= e($u->role) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isSelf): ?>
                                    <span class="rbac-rawrole">تغییر نقش خودتان مجاز نیست</span>
                                <?php else: ?>
                                    <form method="post" action="/admin/roles/users/<?= (int) $u->id ?>/role" class="rbac-assign">
                                        <?= csrf_field() ?>
                                        <select class="field__input rbac-select" name="role">
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= e($role->slug) ?>" <?= $normal === $role->slug ? 'selected' : '' ?>>
                                                    <?= e($role->name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-ghost btn-sm" href="/admin/roles/users/<?= (int) $u->id ?>/overrides">تنظیم دسترسی</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php $baseUrl = '/admin/roles/users'; require dirname(__DIR__, 2) . '/partials/pagination.php'; ?>
</section>
