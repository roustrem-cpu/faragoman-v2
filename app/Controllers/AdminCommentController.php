<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\CommentService;

/**
 * Admin — Comment Moderation (Task G).
 *
 * List, approve, reject and delete comments under `/admin/comments`. Access is
 * gated upstream by [AuthMiddleware, gate.comments] (the `comments.moderate`
 * permission); write routes additionally pass through CsrfMiddleware.
 *
 * Backward compatible: only existing legacy `comments` columns are read/written
 * and the status column is only ever set to 'approved' / 'pending' (the exact
 * values the legacy code uses). See CommentService for the status mapping.
 */
final class AdminCommentController extends Controller
{
    public function __construct(
        View $view,
        private CommentService $comments,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $filter = $this->comments->normaliseFilter(trim((string) $request->query('status', 'pending')));
        $page   = max(1, (int) $request->query('page_num', 1));

        return $this->renderWith('layouts/admin', 'admin/comments/index', [
            'title'        => 'مدیریت دیدگاه‌ها — فراگمان',
            'heading'      => 'مدیریت دیدگاه‌ها',
            'activeNav'    => 'comments',
            'currentUser'  => $this->auth->userModel(),
            'comments'     => $this->comments->list($filter, $page),
            'filter'       => $filter,
            'total'        => $this->comments->total($filter),
            'pendingCount' => $this->comments->pendingCount(),
            'page'         => $page,
            'totalPages'   => $this->comments->totalPages($filter),
            'flash'        => $this->flash((string) $request->query('m', '')),
        ]);
    }

    public function approve(Request $request, string $id): Response
    {
        if ($this->comments->find((int) $id) === null) {
            return $this->notFound();
        }

        $this->comments->approve((int) $id);

        return Response::redirect('/admin/comments?m=approved');
    }

    public function reject(Request $request, string $id): Response
    {
        if ($this->comments->find((int) $id) === null) {
            return $this->notFound();
        }

        $this->comments->reject((int) $id);

        return Response::redirect('/admin/comments?m=rejected');
    }

    public function destroy(Request $request, string $id): Response
    {
        if ($this->comments->find((int) $id) === null) {
            return $this->notFound();
        }

        $this->comments->delete((int) $id);

        return Response::redirect('/admin/comments?m=deleted');
    }

    // ------------------------------------------------------------------ //

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
            'approved' => 'دیدگاه تأیید و منتشر شد.',
            'rejected' => 'دیدگاه از حالت انتشار خارج شد.',
            'deleted'  => 'دیدگاه حذف شد.',
            default    => null,
        };
    }
}
