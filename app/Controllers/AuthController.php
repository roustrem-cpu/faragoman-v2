<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;
use App\Support\Validator;

final class AuthController extends Controller
{
    public function __construct(View $view, private AuthService $auth)
    {
        parent::__construct($view);
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/');
        }

        return $this->render('auth.login', ['title' => 'ورود به فراگمان', 'error' => null]);
    }

    public function login(Request $request): Response
    {
        $validator = new Validator($request->all(), [
            'login'    => ['required'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->render('auth.login', [
                'title' => 'ورود به فراگمان',
                'error' => $validator->firstError(),
            ], 422);
        }

        $userId = $this->auth->attempt(
            (string) $request->input('login'),
            (string) $request->input('password'),
        );

        if ($userId === null) {
            return $this->render('auth.login', [
                'title' => 'ورود به فراگمان',
                'error' => 'نام کاربری یا رمز عبور نادرست است.',
            ], 401);
        }

        return Response::redirect('/');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return Response::redirect('/');
    }
}
