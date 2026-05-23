<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function flash_message(): ?array
{
    return Session::pull('flash');
}

function selected(mixed $left, mixed $right): string
{
    return (string) $left === (string) $right ? 'selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

function format_datetime(?string $value, string $format = 'Y/m/d H:i'): string
{
    if (!$value) {
        return '-';
    }

    return (new DateTimeImmutable($value))->format($format);
}

function view(string $view, array $data = [], string $layout = 'layouts/app'): void
{
    View::render($view, $data, $layout);
}

