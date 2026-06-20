<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

/**
 * Search page — keyword search across published articles.
 * The query is read from `?q=`; an empty query renders the prompt state.
 */
final class SearchController extends Controller
{
    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $q    = trim((string) $request->query('q', ''));
        $page = (int) $request->query('page_num', 1);

        return $this->render('search', [
            'title'       => ($q !== '' ? "جستجو: {$q}" : 'جستجو') . ' — فراگمان',
            'q'           => $q,
            'articles'    => $this->articles->searchResults($q, $page),
            'page'        => max(1, $page),
            'totalPages'  => $this->articles->searchTotalPages($q),
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
