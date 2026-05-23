<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Session;

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Session.php';
require_once __DIR__ . '/../app/Core/Csrf.php';
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/View.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = __DIR__ . '/../app/' . $relative . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

Env::load(__DIR__ . '/../.env');

$timezone = env_value('APP_TIMEZONE', 'Asia/Tokyo') ?: 'Asia/Tokyo';
date_default_timezone_set($timezone);

if (env_value('APP_ENV', 'local') === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

Session::start();

