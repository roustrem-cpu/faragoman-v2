<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ArticleService;
use App\Services\AuthService;
use App\Services\StoryService;

final class HomeController extends Controller
{
    public function __construct(
        View $view,
        private ArticleService $articles,
        private AuthService $auth,
        private StoryService $stories,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page_num', 1);

        return $this->render('home', [
            'title'       => 'فراگمان — خانه',
            'articles'    => $this->articles->homeFeed($page),
            'stories'     => $this->stories->active(),
            'page'        => $page,
            'totalPages'  => $this->articles->totalPages(),
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
