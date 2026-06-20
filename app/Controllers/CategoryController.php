<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Category listing page — published articles belonging to one category.
 * Read-only; resolves data through the existing ArticleService/Repository.
 */
final class CategoryController extends Controller
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
        $categoryId = (int) $id;
        $page       = (int) $request->query('page_num', 1);

        $name     = $categoryId > 0 ? $this->articles->categoryName($categoryId) : null;
        $articles = $categoryId > 0 ? $this->articles->categoryFeed($categoryId, $page) : [];

        // Unknown category (no name and no articles) → graceful 404.
        if ($name === null && $articles === []) {
            return $this->render('errors.404', [
                'title'       => 'یافت نشد — فراگمان',
                'currentUser' => $this->auth->userModel(),
            ], 404);
        }

        return $this->render('category', [
            'title'        => ($name ?? 'دسته‌بندی') . ' — فراگمان',
            'categoryId'   => $categoryId,
            'categoryName' => $name,
            'articles'     => $articles,
            'page'         => max(1, $page),
            'totalPages'   => $this->articles->categoryTotalPages($categoryId),
            'currentUser'  => $this->auth->userModel(),
        ]);
    }
}
