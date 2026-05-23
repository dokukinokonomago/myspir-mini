<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Services\GoogleCalendarService;

class GoogleController
{
    private GoogleCalendarService $service;

    public function __construct()
    {
        $this->service = new GoogleCalendarService();
    }

    public function index(): void
    {
        $user = Auth::user();
        $calendarInfo = null;
        $connectionError = null;

        if ($user && !empty($user['google_access_token']) && !empty($user['google_refresh_token'])) {
            try {
                $calendarInfo = $this->service->fetchPrimaryCalendar($user);
            } catch (\Throwable $exception) {
                $connectionError = $exception->getMessage();
            }
        }

        view('admin/google/index', [
            'pageTitle' => 'Google連携設定',
            'user' => $user,
            'calendarInfo' => $calendarInfo,
            'connectionError' => $connectionError,
            'isConfigured' => (bool) env_value('GOOGLE_CLIENT_ID') && (bool) env_value('GOOGLE_CLIENT_SECRET'),
            'isAdminArea' => true,
        ]);
    }

    public function connect(): void
    {
        if (!env_value('GOOGLE_CLIENT_ID') || !env_value('GOOGLE_CLIENT_SECRET')) {
            Session::flash('error', 'Google Client ID / Secret が未設定です。');
            redirect('/admin/google');
        }

        redirect($this->service->buildAuthUrl());
    }

    public function callback(): void
    {
        $user = Auth::user();

        if (!$user) {
            redirect('/admin/login');
        }

        if (!$this->service->validateState($_GET['state'] ?? null)) {
            Session::flash('error', 'Google OAuth の state 検証に失敗しました。');
            redirect('/admin/google');
        }

        $code = (string) ($_GET['code'] ?? '');

        if ($code === '') {
            Session::flash('error', 'Google 認可コードを取得できませんでした。');
            redirect('/admin/google');
        }

        try {
            $tokens = $this->service->exchangeCodeForTokens($code);
            $this->service->saveTokens((int) $user['id'], $tokens, $user['google_refresh_token'] ?? null);
            Session::flash('success', 'Google カレンダー連携が完了しました。');
        } catch (\Throwable $exception) {
            Session::flash('error', 'Google 連携に失敗しました: ' . $exception->getMessage());
        }

        redirect('/admin/google');
    }

    public function disconnect(): void
    {
        $user = Auth::user();

        if ($user) {
            $this->service->clearTokens((int) $user['id']);
        }

        Session::flash('success', 'Google 連携を解除しました。');
        redirect('/admin/google');
    }
}

