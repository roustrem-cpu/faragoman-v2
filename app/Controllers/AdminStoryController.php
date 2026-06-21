<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Story;
use App\Services\AuthService;
use App\Services\ImageUploadService;
use App\Services\StoryService;
use RuntimeException;

/**
 * Admin — Stories Management (Task H; image uploads added in Task J).
 *
 * List / create / edit / reorder / activate / deactivate / delete the additive
 * `stories` table under `/admin/stories`. Access is gated upstream by
 * [AuthMiddleware, gate.stories] (the `stories.manage` permission); write routes
 * additionally pass through CsrfMiddleware.
 *
 * Backward compatible: only the legacy-guaranteed columns are written for
 * create/edit; the optional `is_active` flag is toggled through the repository's
 * guarded path (a safe no-op on databases where the column was never added).
 *
 * Task J: the story image is now a real file upload (`image_file`), validated +
 * stored by ImageUploadService. The resulting web-relative path is written into
 * the SAME `image_url` column (no schema change). A new image is required when
 * creating a story; on edit the existing image is preserved unless replaced.
 */
final class AdminStoryController extends Controller
{
    public function __construct(
        View $view,
        private StoryService $stories,
        private AuthService $auth,
        private ImageUploadService $uploads,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        return $this->renderWith('layouts/admin', 'admin/stories/index', [
            'title'          => 'مدیریت استوری‌ها — فراگمان',
            'heading'        => 'مدیریت استوری‌ها',
            'activeNav'      => 'stories',
            'currentUser'    => $this->auth->userModel(),
            'stories'        => $this->stories->all(),
            'supportsActive' => $this->stories->supportsActiveFlag(),
            'flash'          => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function store(Request $request): Response
    {
        $data = $this->collect($request);

        // Image upload (required on create) — resolves $data['image_url'].
        $uploadError = $this->applyUpload($request, $data, '');

        $errors = $this->validate($data);
        if ($uploadError !== null) {
            $errors['image_file'] = $uploadError;
        }

        if ($errors !== []) {
            return $this->form(null, $data, $errors, 422);
        }

        $this->stories->create(
            $data['title'],
            $data['image_url'],
            $data['link_url'] !== '' ? $data['link_url'] : null,
            $data['display_order'],
        );

        return Response::redirect('/admin/stories?m=created');
    }

    public function edit(Request $request, string $id): Response
    {
        $story = $this->stories->find((int) $id);

        if ($story === null) {
            return $this->notFound();
        }

        return $this->form($story);
    }

    public function update(Request $request, string $id): Response
    {
        $story = $this->stories->find((int) $id);

        if ($story === null) {
            return $this->notFound();
        }

        $data = $this->collect($request);

        // Keep the current image unless a new file is uploaded (Task J).
        $uploadError = $this->applyUpload($request, $data, (string) ($story->imageUrl ?? ''));

        $errors = $this->validate($data);
        if ($uploadError !== null) {
            $errors['image_file'] = $uploadError;
        }

        if ($errors !== []) {
            return $this->form($story, $data, $errors, 422);
        }

        $this->stories->update(
            $story->id,
            $data['title'],
            $data['image_url'],
            $data['link_url'] !== '' ? $data['link_url'] : null,
            $data['display_order'],
        );

        return Response::redirect('/admin/stories?m=updated');
    }

    public function activate(Request $request, string $id): Response
    {
        return $this->toggle((int) $id, true);
    }

    public function deactivate(Request $request, string $id): Response
    {
        return $this->toggle((int) $id, false);
    }

    public function moveUp(Request $request, string $id): Response
    {
        return $this->shift((int) $id, 'up');
    }

    public function moveDown(Request $request, string $id): Response
    {
        return $this->shift((int) $id, 'down');
    }

    public function destroy(Request $request, string $id): Response
    {
        if ($this->stories->find((int) $id) === null) {
            return $this->notFound();
        }

        $this->stories->delete((int) $id);

        return Response::redirect('/admin/stories?m=deleted');
    }

    // ------------------------------------------------------------------ //

    /**
     * Resolve image_url for this request: a freshly uploaded file wins,
     * otherwise the supplied fallback (existing value / empty) is preserved.
     *
     * @param array<string, mixed> $data
     * @return string|null validation error message, or null on success/no-upload
     */
    private function applyUpload(Request $request, array &$data, string $fallback): ?string
    {
        $data['image_url'] = $fallback;

        $file = $request->file('image_file');
        if (!$this->uploads->hasUpload($file)) {
            return null;
        }

        try {
            $data['image_url'] = $this->uploads->store($file, 'stories');

            return null;
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
    }

    private function toggle(int $id, bool $active): Response
    {
        if ($this->stories->find($id) === null) {
            return $this->notFound();
        }

        if (!$this->stories->supportsActiveFlag()) {
            return Response::redirect('/admin/stories?m=active_unsupported');
        }

        $this->stories->setActive($id, $active);

        return Response::redirect('/admin/stories?m=' . ($active ? 'activated' : 'deactivated'));
    }

    private function shift(int $id, string $direction): Response
    {
        if ($this->stories->find($id) === null) {
            return $this->notFound();
        }

        $this->stories->move($id, $direction);

        return Response::redirect('/admin/stories?m=moved');
    }

    /**
     * @param array<string, mixed>  $old
     * @param array<string, string> $errors
     */
    private function form(?Story $story, array $old = [], array $errors = [], int $status = 200): Response
    {
        $isEdit = $story !== null;

        return $this->renderWith('layouts/admin', 'admin/stories/form', [
            'title'       => ($isEdit ? 'ویرایش استوری' : 'استوری جدید') . ' — فراگمان',
            'heading'     => $isEdit ? 'ویرایش استوری' : 'استوری جدید',
            'activeNav'   => 'stories',
            'currentUser' => $this->auth->userModel(),
            'story'       => $story,
            'isEdit'      => $isEdit,
            'formAction'  => $isEdit ? '/admin/stories/' . $story->id : '/admin/stories',
            'old'         => $old,
            'errors'      => $errors,
        ], $status);
    }

    /**
     * @return array{title:string,image_url:string,link_url:string,display_order:int}
     */
    private function collect(Request $request): array
    {
        return [
            'title'         => trim((string) $request->input('title', '')),
            'image_url'     => trim((string) $request->input('image_url', '')),
            'link_url'      => trim((string) $request->input('link_url', '')),
            'display_order' => (int) $request->input('display_order', 0),
        ];
    }

    /**
     * @param array{title:string,image_url:string,link_url:string,display_order:int} $data
     * @return array<string, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'عنوان الزامی است.';
        } elseif (mb_strlen($data['title']) > 255) {
            $errors['title'] = 'عنوان نباید بیش از ۲۵۵ کاراکتر باشد.';
        }

        if ($data['image_url'] === '') {
            $errors['image_file'] = 'انتخاب تصویر الزامی است.';
        } elseif (mb_strlen($data['image_url']) > 512) {
            $errors['image_file'] = 'نشانی تصویر بیش از حد طولانی است.';
        }

        if (mb_strlen($data['link_url']) > 512) {
            $errors['link_url'] = 'نشانی پیوند بیش از حد طولانی است.';
        }

        return $errors;
    }

    private function notFound(): Response
    {
        return $this->render('errors.404', [
            'title'       => 'یافت نشد — فراگمان',
            'currentUser' => $this->auth->userModel(),
        ], 404);
    }

    private function flash(string $m): ?string
    {
        return match ($m) {
            'created'            => 'استوری ایجاد شد.',
            'updated'            => 'استوری به‌روزرسانی شد.',
            'deleted'            => 'استوری حذف شد.',
            'activated'          => 'استوری فعال شد.',
            'deactivated'        => 'استوری غیرفعال شد.',
            'moved'              => 'ترتیب استوری‌ها به‌روزرسانی شد.',
            'active_unsupported' => 'پایگاه‌دادهٔ فعلی ستون فعال/غیرفعال را ندارد؛ این قابلیت در دسترس نیست.',
            default              => null,
        };
    }
}
