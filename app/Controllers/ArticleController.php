<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Public article detail page.
 *
 * Resolves an article by its (decoded) title — the exact identifier the home
 * feed already links to (`/{rawurlencode(title)}`). This closes the 404 gap
 * that existed because the home cards pointed at a route that did not exist.
 *
 * Reads only: it leans on the existing ArticleRepository (via ArticleService)
 * and performs no schema or data mutations, preserving full backward
 * compatibility with the production database.
 */
final class ArticleController extends Controller
{
    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function show(Request $request, string $title): Response
    {
        $article = $this->articles->findByTitle($title);

        // Only published articles are publicly visible; everything else 404s
        // gracefully through the shared error view (still wrapped in layout).
        if ($article === null || $article->status !== 'published') {
            return $this->render('errors.404', [
                'title'       => 'یافت نشد — فراگمان',
                'currentUser' => $this->auth->userModel(),
            ], 404);
        }

        return $this->render('article', [
            'title'       => $article->title . ' — فراگمان',
            'article'     => $article,
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
