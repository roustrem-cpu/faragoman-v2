<?php
/**
 * Shared create/edit story form.
 *
 * @var \App\Models\Story|null   $story
 * @var bool                     $isEdit
 * @var string                   $formAction
 * @var array<string, mixed>     $old
 * @var array<string, string>    $errors
 */
$title    = (string) ($old['title']         ?? ($story->title        ?? ''));
$imageUrl = (string) ($old['image_url']     ?? ($story->imageUrl     ?? ''));
$linkUrl  = (string) ($old['link_url']      ?? ($story->linkUrl      ?? ''));
$order    = (int)    ($old['display_order'] ?? ($story->displayOrder ?? 0));
?>
<section class="admin-section admin-formwrap">
    <form class="card form admin-form" method="post" action="<?= e($formAction) ?>">
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="title">عنوان</label>
            <input class="field__input" id="title" name="title" value="<?= e($title) ?>" required>
            <?php if (!empty($errors['title'])): ?><span class="field__error"><?= e($errors['title']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label class="field__label" for="image_url">نشانی تصویر</label>
            <input class="field__input" id="image_url" name="image_url" value="<?= e($imageUrl) ?>" placeholder="uploads/stories/…" required>
            <span class="field__hint">آپلود تصویر در گام بعدی (Task J) اضافه می‌شود؛ فعلاً نشانی/مسیر تصویر را وارد کنید.</span>
            <?php if (!empty($errors['image_url'])): ?><span class="field__error"><?= e($errors['image_url']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="link_url">پیوند (اختیاری)</label>
                <input class="field__input" id="link_url" name="link_url" dir="ltr" value="<?= e($linkUrl) ?>" placeholder="https://…">
                <?php if (!empty($errors['link_url'])): ?><span class="field__error"><?= e($errors['link_url']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="display_order">ترتیب نمایش</label>
                <input class="field__input" id="display_order" name="display_order" type="number" dir="ltr" value="<?= e($order) ?>">
                <span class="field__hint">عدد کوچک‌تر زودتر نمایش داده می‌شود.</span>
            </div>
        </div>

        <div class="admin-form__actions">
            <a href="/admin/stories" class="btn btn-ghost">انصراف</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد استوری' ?></button>
        </div>
    </form>
</section>
