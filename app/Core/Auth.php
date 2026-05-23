<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        Session::put('user_id', (int) $user['id']);

        return true;
    }

    public static function check(): bool
    {
        return (bool) Session::get('user_id');
    }

    public static function user(): ?array
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        session_regenerate_id(true);
    }
}
