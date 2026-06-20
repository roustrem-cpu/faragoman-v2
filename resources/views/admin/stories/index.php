<?php
/**
 * Admin stories management — list / reorder / activate / delete.
 *
 * @var array<int, \App\Models\Story> $stories
 * @var bool        $supportsActive
 * @var string|null $flash
 */
?>
<section class="admin-section">
    <?php if (!empty($flash)): ?>
        <div class="alert alert-success"><?= e($flash) ?></div>
    <?php endif; ?>

    <div class="admin-toolbar">
        <p class="admin-toolbar__count"><?= e(number_format(count($stories))) ?> استوری</p>
        <a href="/admin/stories/create" class="btn btn-primary">+ استوری جدید</a>
    </div>

    <?php if (!$supportsActive): ?>
        <div class="alert"><p>ستون اختیاری <code>is_active</code> در این پایگاه‌داده وجود ندارد؛ همهٔ استوری‌ها فعال در نظر گرفته می‌شوند و دکمهٔ فعال/غیرفعال نمایش داده نمی‌شود.</p></div>
    <?php endif; ?>

    <?php if ($stories === []): ?>
        <div class="empty-state card"><p>هنوز استوری‌ای ثبت نشده است.</p></div>
    <?php else: ?>
        <div class="card admin-tablewrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>تصویر</th>
                        <th>عنوان</th>
                        <th>پیوند</th>
                        <th>ترتیب</th>
                        <th>وضعیت</th>
                        <th class="admin-table__actions-h">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stories as $i => $s): ?>
                        <tr>
                            <td>
                                <?php if ($s->imageUrl !== ''): ?>
                                    <img class="story-thumb" src="<?= e($s->imageUrl) ?>" alt="" loading="lazy">
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="admin-table__title"><?= e($s->title) ?></td>
                            <td dir="ltr" class="story-link">
                                <?php if ($s->linkUrl !== null && $s->linkUrl !== ''): ?>
                                    <a href="<?= e($s->linkUrl) ?>" target="_blank" rel="noopener"><?= e($s->linkUrl) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td dir="ltr"><?= e($s->displayOrder) ?></td>
                            <td>
                                <?php if ($s->isActive): ?>
                                    <span class="tag tag--active">فعال</span>
                                <?php else: ?>
                                    <span class="tag tag--banned">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <form method="post" action="/admin/stories/<?= (int) $s->id ?>/move-up">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-sm" type="submit" title="بالا" <?= $i === 0 ? 'disabled' : '' ?>>▲</button>
                                    </form>
                                    <form method="post" action="/admin/stories/<?= (int) $s->id ?>/move-down">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-sm" type="submit" title="پایین" <?= $i === count($stories) - 1 ? 'disabled' : '' ?>>▼</button>
                                    </form>
                                    <a class="btn btn-ghost btn-sm" href="/admin/stories/<?= (int) $s->id ?>/edit">ویرایش</a>
                                    <?php if ($supportsActive): ?>
                                        <?php if ($s->isActive): ?>
                                            <form method="post" action="/admin/stories/<?= (int) $s->id ?>/deactivate">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-ghost btn-sm" type="submit">غیرفعال</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="/admin/stories/<?= (int) $s->id ?>/activate">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-primary btn-sm" type="submit">فعال</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" action="/admin/stories/<?= (int) $s->id ?>/delete" onsubmit="return confirm('آیا از حذف این استوری مطمئن هستید؟');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
