<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Author listing page — published articles written by one author.
 * Read-only; resolves data through the existing ArticleService/Repository.
 */
final class AuthorController extends Controller
{
    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function show(Request $request, string $id): Response
    {
        $userId = (int) $id;
        $page   = (int) $request->query('page_num', 1);

        $name     = $userId > 0 ? $this->articles->authorName($userId) : null;
        $articles = $userId > 0 ? $this->articles->authorFeed($userId, $page) : [];

        if ($name === null && $articles === []) {
            return $this->render('errors.404', [
                'title'       => 'یافت نشد — فراگمان',
                'currentUser' => $this->auth->userModel(),
            ], 404);
        }

        return $this->render('author', [
            'title'       => ($name ?? 'نویسنده') . ' — فراگمان',
            'authorId'    => $userId,
            'authorName'  => $name,
            'articles'    => $articles,
            'page'        => max(1, $page),
            'totalPages'  => $this->articles->authorTotalPages($userId),
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
