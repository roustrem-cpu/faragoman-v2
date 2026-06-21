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
    <form class="card form admin-form" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="title">عنوان</label>
            <input class="field__input" id="title" name="title" value="<?= e($title) ?>" required>
            <?php if (!empty($errors['title'])): ?><span class="field__error"><?= e($errors['title']) ?></span><?php endif; ?>
        </div>

        <div class="field">
            <label class="field__label" for="image_file">تصویر</label>
            <?php if ($imageUrl !== ''): ?>
                <div class="admin-form__preview">
                    <img src="/<?= e(ltrim($imageUrl, '/')) ?>" alt="" loading="lazy">
                    <span class="field__hint">تصویر فعلی — برای جایگزینی، فایل تازه‌ای انتخاب کنید.</span>
                </div>
            <?php endif; ?>
            <input class="field__input" id="image_file" name="image_file" type="file" accept="image/jpeg,image/png,image/webp" <?= $isEdit ? '' : 'required' ?>>
            <input type="hidden" name="image_url" value="<?= e($imageUrl) ?>">
            <span class="field__hint">قالب‌های مجاز: JPEG، PNG، WEBP — حداکثر ۵ مگابایت.</span>
            <?php if (!empty($errors['image_file'])): ?><span class="field__error"><?= e($errors['image_file']) ?></span><?php endif; ?>
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
