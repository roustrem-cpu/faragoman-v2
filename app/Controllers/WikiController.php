<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Services\WikiService;

/**
 * Public knowledge-base / glossary (Task I).
 *
 *  - index() lists every published term (full-page terms link to their page).
 *  - show($slug) renders a single published term, or a graceful 404.
 *
 * Read-only; resolves data through WikiService (which degrades gracefully if
 * the legacy `wiki_terms` table is absent).
 */
final class WikiController extends Controller
{
    public function __construct(
        View $view,
        private WikiService $wiki,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        return $this->render('wiki/index', [
            'title'       => 'دانشنامه — فراگمان',
            'terms'       => $this->wiki->list(),
            'currentUser' => $this->auth->userModel(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $term = $this->wiki->find($slug);

        if ($term === null) {
            return $this->render('errors.404', [
                'title'       => 'یافت نشد — فراگمان',
                'currentUser' => $this->auth->userModel(),
            ], 404);
        }

        return $this->render('wiki/show', [
            'title'       => $term->term . ' — دانشنامه فراگمان',
            'term'        => $term,
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
