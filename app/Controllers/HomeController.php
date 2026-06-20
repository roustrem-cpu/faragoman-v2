<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;

final class HomeController extends Controller
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
        $page = (int) $request->query('page_num', 1);

        return $this->render('home', [
            'title'      => 'فراگمان — خانه',
            'articles'   => $this->articles->homeFeed($page),
            'page'       => $page,
            'totalPages' => $this->articles->totalPages(),
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
