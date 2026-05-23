<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use App\Core\Csrf;
use App\Core\Router;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    echo 'CSRF token mismatch.';
    exit;
}

$router = new Router();
require __DIR__ . '/../config/routes.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
