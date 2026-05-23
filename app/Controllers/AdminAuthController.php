<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;

class AdminAuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/admin');
        }

        view('admin/login', [
            'pageTitle' => '管理者ログイン',
        ]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            view('admin/login', [
                'pageTitle' => '管理者ログイン',
                'error' => 'メールアドレスとパスワードを入力してください。',
                'email' => $email,
            ]);
            return;
        }

        if (!Auth::attempt($email, $password)) {
            view('admin/login', [
                'pageTitle' => '管理者ログイン',
                'error' => 'ログイン情報が正しくありません。',
                'email' => $email,
            ]);
            return;
        }

        Session::flash('success', 'ログインしました。');
        redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'ログアウトしました。');
        redirect('/admin/login');
    }
}

