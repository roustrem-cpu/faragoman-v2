<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\User;
use App\Services\ArticleService;
use App\Services\AuthService;
use App\Services\UserService;

/**
 * Public user-profile page (Task I).
 *
 *  - me()        the signed-in user's own profile (`/profile`; AuthMiddleware
 *                guards it, so guests are redirected to login upstream).
 *  - show($id)   any user's public profile (`/profile/{id}`), or a graceful 404.
 *
 * Read-only: profile fields come from UserService and the author's published
 * articles are resolved through the existing ArticleService author feed. The
 * private email column is never exposed.
 */
final class ProfileController extends Controller
{
    public function __construct(
        View $view,
        private UserService $users,
        private ArticleService $articles,
        private AuthService $auth,
    ) {
        parent::__construct($view);
    }

    public function me(Request $request): Response
    {
        $user = $this->auth->userModel();

        if ($user === null) {
            return Response::redirect('/login');
        }

        return $this->profile($user, $request, '/profile');
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->users->find((int) $id);

        if ($user === null) {
            return $this->render('errors.404', [
                'title'       => 'یافت نشد — فراگمان',
                'currentUser' => $this->auth->userModel(),
            ], 404);
        }

        return $this->profile($user, $request, '/profile/' . $user->id);
    }

    private function profile(User $user, Request $request, string $baseUrl): Response
    {
        $page = max(1, (int) $request->query('page_num', 1));

        return $this->render('profile', [
            'title'       => $user->name() . ' — فراگمان',
            'profileUser' => $user,
            'articles'    => $this->articles->authorFeed($user->id, $page),
            'page'        => $page,
            'totalPages'  => $this->articles->authorTotalPages($user->id),
            'baseUrl'     => $baseUrl,
            'currentUser' => $this->auth->userModel(),
        ]);
    }
}
