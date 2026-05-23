<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;

class GoogleCalendarService
{
    private const CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar';

    public function buildAuthUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Session::put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => env_value('GOOGLE_CLIENT_ID', ''),
            'redirect_uri' => env_value('GOOGLE_REDIRECT_URI', ''),
            'response_type' => 'code',
            'scope' => self::CALENDAR_SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public function validateState(?string $state): bool
    {
        $stored = Session::pull('google_oauth_state');

        return is_string($state) && is_string($stored) && hash_equals($stored, $state);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = $this->oauthToken([
            'code' => $code,
            'client_id' => env_value('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env_value('GOOGLE_CLIENT_SECRET', ''),
            'redirect_uri' => env_value('GOOGLE_REDIRECT_URI', ''),
            'grant_type' => 'authorization_code',
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Google のアクセストークン取得に失敗しました。');
        }

        return $response;
    }

    public function saveTokens(int $userId, array $tokens, ?string $existingRefreshToken = null): void
    {
        $refreshToken = $tokens['refresh_token'] ?? $existingRefreshToken;
        $expiresAt = (new DateTimeImmutable())->add(
            new DateInterval('PT' . ((int) ($tokens['expires_in'] ?? 3600)) . 'S')
        )->format('Y-m-d H:i:s');

        $statement = Database::connection()->prepare(
            'UPDATE users
             SET google_access_token = :access_token,
                 google_refresh_token = :refresh_token,
                 google_token_expires_at = :expires_at,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $userId,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);
    }

    public function clearTokens(int $userId): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET google_access_token = NULL,
                 google_refresh_token = NULL,
                 google_token_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $statement->execute(['id' => $userId]);
    }

    public function fetchPrimaryCalendar(array $user): array
    {
        $accessToken = $this->ensureAccessToken($user);

        return $this->apiRequest(
            'GET',
            'https://www.googleapis.com/calendar/v3/calendars/primary',
            $accessToken
        );
    }

    public function createEventWithMeet(array $user, array $booking): array
    {
        $accessToken = $this->ensureAccessToken($user);
        $timezone = env_value('APP_TIMEZONE', 'Asia/Tokyo') ?: 'Asia/Tokyo';
        $requestId = bin2hex(random_bytes(16));

        $payload = [
            'summary' => '【面談】' . $booking['client_name'] . ' 様',
            'description' => implode("\n", [
                '名前: ' . $booking['client_name'],
                '会社名: ' . ($booking['company_name'] ?: '-'),
                'メールアドレス: ' . $booking['client_email'],
                '電話番号: ' . $booking['client_phone'],
                '相談内容: ' . ($booking['message'] ?: '-'),
                '予約日時: ' . format_datetime($booking['booked_start_datetime']) . ' - ' . format_datetime($booking['booked_end_datetime'], 'H:i'),
                'この予定はアプリから自動作成されました。',
            ]),
            'start' => [
                'dateTime' => (new DateTimeImmutable($booking['booked_start_datetime']))->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => (new DateTimeImmutable($booking['booked_end_datetime']))->format(DATE_ATOM),
                'timeZone' => $timezone,
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => $requestId,
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ];

        $event = $this->apiRequest(
            'POST',
            'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1',
            $accessToken,
            $payload
        );

        if (empty($event['id'])) {
            throw new RuntimeException('Google カレンダー予定の作成に失敗しました。');
        }

        $meetUrl = $this->extractMeetUrl($event);

        if (!$meetUrl) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                sleep(1);
                $event = $this->apiRequest(
                    'GET',
                    'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode((string) $event['id']) . '?conferenceDataVersion=1',
                    $accessToken
                );
                $meetUrl = $this->extractMeetUrl($event);
                if ($meetUrl) {
                    break;
                }
            }
        }

        if (!$meetUrl) {
            $this->deleteEvent($user, (string) $event['id']);
            throw new RuntimeException('Google Meet リンクの発行に失敗しました。時間をおいて再度お試しください。');
        }

        return [
            'event_id' => (string) $event['id'],
            'meet_url' => $meetUrl,
        ];
    }

    public function deleteEvent(array $user, string $eventId): void
    {
        try {
            $accessToken = $this->ensureAccessToken($user);
            $this->apiRequest(
                'DELETE',
                'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode($eventId),
                $accessToken
            );
        } catch (\Throwable) {
        }
    }

    private function ensureAccessToken(array $user): string
    {
        $accessToken = $user['google_access_token'] ?? null;
        $refreshToken = $user['google_refresh_token'] ?? null;
        $expiresAt = $user['google_token_expires_at'] ?? null;

        if (!$accessToken || !$refreshToken || !$expiresAt) {
            throw new RuntimeException('Google 連携が未設定です。');
        }

        $expiration = new DateTimeImmutable($expiresAt);
        $threshold = (new DateTimeImmutable())->add(new DateInterval('PT60S'));

        if ($expiration > $threshold) {
            return $accessToken;
        }

        $refreshed = $this->oauthToken([
            'client_id' => env_value('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env_value('GOOGLE_CLIENT_SECRET', ''),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (empty($refreshed['access_token'])) {
            throw new RuntimeException('Google アクセストークンの更新に失敗しました。');
        }

        $this->saveTokens((int) $user['id'], $refreshed, $refreshToken);
        return (string) $refreshed['access_token'];
    }

    private function oauthToken(array $formData): array
    {
        return $this->rawRequest(
            'POST',
            'https://oauth2.googleapis.com/token',
            null,
            $formData,
            true
        );
    }

    private function apiRequest(string $method, string $url, string $accessToken, ?array $payload = null): array
    {
        return $this->rawRequest($method, $url, $accessToken, $payload, false);
    }

    private function rawRequest(
        string $method,
        string $url,
        ?string $accessToken = null,
        ?array $payload = null,
        bool $isForm = false
    ): array {
        $ch = curl_init($url);

        $headers = ['Accept: application/json'];

        if ($accessToken) {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $isForm ? http_build_query($payload) : json_encode($payload, JSON_UNESCAPED_UNICODE));
            $headers[] = $isForm
                ? 'Content-Type: application/x-www-form-urlencoded'
                : 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError !== '') {
            throw new RuntimeException('Google API 通信エラー: ' . $curlError);
        }

        if ($statusCode === 204 && $method === 'DELETE') {
            return [];
        }

        $decoded = json_decode($responseBody, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Google API 応答の解析に失敗しました。');
        }

        if ($statusCode >= 400) {
            $message = $decoded['error_description']
                ?? $decoded['error']['message']
                ?? 'Google API エラー';
            throw new RuntimeException($message);
        }

        return $decoded;
    }

    private function extractMeetUrl(array $event): ?string
    {
        if (!empty($event['hangoutLink'])) {
            return (string) $event['hangoutLink'];
        }

        foreach (($event['conferenceData']['entryPoints'] ?? []) as $entryPoint) {
            if (($entryPoint['entryPointType'] ?? '') === 'video' && !empty($entryPoint['uri'])) {
                return (string) $entryPoint['uri'];
            }
        }

        return null;
    }
}

