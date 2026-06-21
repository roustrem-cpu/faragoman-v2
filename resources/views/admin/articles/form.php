<?php
/**
 * Shared create/edit article form.
 * @var \App\Models\Article|null $article
 * @var bool   $isEdit
 * @var string $formAction
 * @var array<int, array{id:int,name:string}> $categories
 * @var array<int, string> $statuses
 * @var array<string, mixed>  $old
 * @var array<string, string> $errors
 */
$statusLabels = [
    'published' => 'منتشر شده',
    'pending'   => 'در انتظار بررسی',
    'approved'  => 'تأییدشده',
    'rejected'  => 'رد شده',
];
$title    = (string) ($old['title']       ?? ($article->title    ?? ''));
$catId    = (int)    ($old['category_id'] ?? ($article->categoryId ?? 0));
$excerpt  = (string) ($old['excerpt']     ?? ($article->excerpt  ?? ''));
$content  = (string) ($old['content']     ?? ($article->content  ?? ''));
$imageUrl = (string) ($old['image_url']   ?? ($article->imageUrl ?? ''));
$postTag  = (string) ($old['post_tag']    ?? ($article->postTag  ?? ''));
$status   = (string) ($old['status']      ?? ($article->status   ?? 'published'));
?>
<section class="admin-section admin-formwrap">
    <form class="card form admin-form" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
            <label class="field__label" for="title">عنوان</label>
            <input class="field__input" id="title" name="title" value="<?= e($title) ?>" required>
            <?php if (!empty($errors['title'])): ?><span class="field__error"><?= e($errors['title']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="category_id">دسته‌بندی</label>
                <select class="field__input" id="category_id" name="category_id" required>
                    <option value="">— انتخاب کنید —</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $catId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['category_id'])): ?><span class="field__error"><?= e($errors['category_id']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="status">وضعیت</label>
                <select class="field__input" id="status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($statusLabels[$s] ?? $s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="excerpt">خلاصه</label>
            <textarea class="field__input" id="excerpt" name="excerpt" rows="2"><?= e($excerpt) ?></textarea>
        </div>

        <div class="field">
            <label class="field__label" for="content">متن مقاله</label>
            <textarea class="field__input admin-form__content" id="content" name="content" rows="12" required><?= e($content) ?></textarea>
            <?php if (!empty($errors['content'])): ?><span class="field__error"><?= e($errors['content']) ?></span><?php endif; ?>
        </div>

        <div class="admin-form__row">
            <div class="field">
                <label class="field__label" for="image_file">تصویر شاخص</label>
                <?php if ($imageUrl !== ''): ?>
                    <div class="admin-form__preview">
                        <img src="/<?= e(ltrim($imageUrl, '/')) ?>" alt="" loading="lazy">
                        <span class="field__hint">تصویر فعلی — برای جایگزینی، فایل تازه‌ای انتخاب کنید.</span>
                    </div>
                <?php endif; ?>
                <input class="field__input" id="image_file" name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
                <input type="hidden" name="image_url" value="<?= e($imageUrl) ?>">
                <span class="field__hint">قالب‌های مجاز: JPEG، PNG، WEBP — حداکثر ۵ مگابایت. (اختیاری)</span>
                <?php if (!empty($errors['image_file'])): ?><span class="field__error"><?= e($errors['image_file']) ?></span><?php endif; ?>
            </div>
            <div class="field">
                <label class="field__label" for="post_tag">برچسب‌ها</label>
                <input class="field__input" id="post_tag" name="post_tag" value="<?= e($postTag) ?>" placeholder="با کاما جدا کنید">
            </div>
        </div>

        <div class="admin-form__actions">
            <a href="/admin/articles" class="btn btn-ghost">انصراف</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'ذخیره تغییرات' : 'ایجاد مقاله' ?></button>
        </div>
    </form>
</section>
