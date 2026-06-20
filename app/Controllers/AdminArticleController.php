<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Article;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Admin — Article Management (Task D).
 *
 * CRUD + publish/unpublish over the existing `articles` table. Access is gated
 * upstream by [AuthMiddleware, gate.admin]; write routes additionally pass
 * through CsrfMiddleware. All data flows through ArticleService → repository,
 * which mirrors the legacy columns for full DB backward compatibility.
 */
final class AdminArticleController extends Controller
{
    /** Status values known to exist in the production `articles` column. */
    private const STATUSES = ['published', 'pending', 'approved', 'rejected'];

    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page_num', 1);

        return $this->renderWith('layouts/admin', 'admin/articles/index', [
            'title'       => 'مدیریت مقاله‌ها — فراگمان',
            'heading'     => 'مدیریت مقاله‌ها',
            'activeNav'   => 'articles',
            'currentUser' => $this->auth->userModel(),
            'articles'    => $this->articles->adminList($page),
            'page'        => max(1, $page),
            'totalPages'  => $this->articles->adminTotalPages(),
            'flash'       => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function store(Request $request): Response
    {
        $data   = $this->collect($request);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return $this->form(null, $data, $errors, 422);
        }

        $user = $this->auth->userModel();
        $this->articles->createArticle($data, $user?->id ?? 0, $user?->name() ?? 'مدیر');

        return Response::redirect('/admin/articles?m=created');
    }

    public function edit(Request $request, string $id): Response
    {
        $article = $this->articles->find((int) $id);

        if ($article === null) {
            return $this->notFound();
        }

        return $this->form($article);
    }

    public function update(Request $request, string $id): Response
    {
        $article = $this->articles->find((int) $id);

        if ($article === null) {
            return $this->notFound();
        }

        $data   = $this->collect($request);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return $this->form($article, $data, $errors, 422);
        }

        $this->articles->updateArticle((int) $id, $data);

        return Response::redirect('/admin/articles?m=updated');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->articles->deleteArticle((int) $id);

        return Response::redirect('/admin/articles?m=deleted');
    }

    public function publish(Request $request, string $id): Response
    {
        $this->articles->setStatus((int) $id, 'published');

        return Response::redirect('/admin/articles?m=published');
    }

    public function unpublish(Request $request, string $id): Response
    {
        $this->articles->setStatus((int) $id, 'pending');

        return Response::redirect('/admin/articles?m=unpublished');
    }

    // ------------------------------------------------------------------ //

    private function form(?Article $article, array $old = [], array $errors = [], int $status = 200): Response
    {
        $isEdit = $article !== null;

        return $this->renderWith('layouts/admin', 'admin/articles/form', [
            'title'       => ($isEdit ? 'ویرایش مقاله' : 'مقاله جدید') . ' — فراگمان',
            'heading'     => $isEdit ? 'ویرایش مقاله' : 'مقاله جدید',
            'activeNav'   => 'articles',
            'currentUser' => $this->auth->userModel(),
            'article'     => $article,
            'isEdit'      => $isEdit,
            'formAction'  => $isEdit ? '/admin/articles/' . $article->id : '/admin/articles',
            'categories'  => $this->articles->categories(),
            'statuses'    => self::STATUSES,
            'old'         => $old,
            'errors'      => $errors,
        ], $status);
    }

    private function notFound(): Response
    {
        return $this->render('errors.404', [
            'title'       => 'یافت نشد — فراگمان',
            'currentUser' => $this->auth->userModel(),
        ], 404);
    }

    /**
     * @return array{title:string,category_id:int,excerpt:string,content:string,image_url:string,post_tag:string,status:string}
     */
    private function collect(Request $request): array
    {
        return [
            'title'       => trim((string) $request->input('title', '')),
            'category_id' => (int) $request->input('category_id', 0),
            'excerpt'     => trim((string) $request->input('excerpt', '')),
            'content'     => (string) $request->input('content', ''),
            'image_url'   => trim((string) $request->input('image_url', '')),
            'post_tag'    => trim((string) $request->input('post_tag', '')),
            'status'      => (string) $request->input('status', 'published'),
        ];
    }

    /**
     * @param array<string, mixed> $d
     * @return array<string, string>
     */
    private function validate(array $d): array
    {
        $errors = [];

        if ($d['title'] === '') {
            $errors['title'] = 'عنوان الزامی است.';
        }
        if ((int) $d['category_id'] <= 0) {
            $errors['category_id'] = 'انتخاب دسته‌بندی الزامی است.';
        }
        if (trim((string) $d['content']) === '') {
            $errors['content'] = 'متن مقاله الزامی است.';
        }
        if (!in_array($d['status'], self::STATUSES, true)) {
            $errors['status'] = 'وضعیت نامعتبر است.';
        }

        return $errors;
    }

    private function flash(string $m): ?string
    {
        return match ($m) {
            'created'     => 'مقاله با موفقیت ایجاد شد.',
            'updated'     => 'تغییرات ذخیره شد.',
            'deleted'     => 'مقاله حذف شد.',
            'published'   => 'مقاله منتشر شد.',
            'unpublished' => 'مقاله از حالت انتشار خارج شد.',
            default       => null,
        };
    }
}
