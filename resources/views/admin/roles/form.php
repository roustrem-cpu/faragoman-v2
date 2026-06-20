<?php
/**
 * Shared create/edit role form.
 * @var \App\Models\Role|null  $role
 * @var bool   $isEdit
 * @var bool   $isCore
 * @var string $formAction
 * @var array<string, mixed>  $old
 * @var array<string, string> $errors
 */
$name = (string) ($old['name'] ?? ($role->name ?? ''));
$slug = (string) ($old['slug'] ?? ($role->slug ?? ''));
$rank = (string) ($old['rank'] ?? ($role->rank ?? 10));
?>
<section class="admin-section admin-formwrap">
    <form class="card form admin-form" method="post" action="<?= e($formAction) ?>">
        <?= csrf_field() ?>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="name">نام نقش</label>
                <input class="field__input" id="name" name="name" value="<?= e($name) ?>" required>
                <?php if (!empty($errors['name'])): ?><span class="field__error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="rank">رتبه (هرچه بزرگ‌تر، قدرتمندتر)</label>
                <input class="field__input" id="rank" name="rank" type="number" min="0" max="1000" value="<?= e($rank) ?>">
                <?php if (!empty($errors['rank'])): ?><span class="field__error"><?= e($errors['rank']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="slug">شناسه (slug)</label>
            <input class="field__input" id="slug" name="slug" value="<?= e($slug) ?>" dir="ltr"
                   placeholder="content_manager" <?= $isCore ? 'readonly' : '' ?>>
            <?php if ($isCore): ?>
                <span class="field__hint">این یک نقش پایه است؛ شناسه‌ی آن قفل شده و قابل تغییر نیست (موتور دسترسی به آن وابسته است).</span>
            <?php else: ?>
                <span class="field__hint">فقط حروف کوچک انگلیسی، عدد و زیرخط. برای مثال: <code>content_manager</code></span>
            <?php endif; ?>
            <?php if (!empty($errors['slug'])): ?><span class="field__error"><?= e($errors['slug']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form__actions">
            <a href="/admin/roles" class="btn btn-ghost">انصراف</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد نقش' ?></button>
        </div>
    </form>
</section>
