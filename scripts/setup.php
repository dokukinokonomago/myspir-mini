<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Env.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;
use App\Core\Env;

Env::load(__DIR__ . '/../.env');

$attempts = 0;
$pdo = null;

while ($attempts < 30) {
    try {
        $pdo = Database::connection();
        break;
    } catch (Throwable $exception) {
        $attempts++;
        fwrite(STDOUT, "Waiting for database... attempt {$attempts}\n");
        sleep(2);
    }
}

if (!$pdo) {
    fwrite(STDERR, "Database connection could not be established.\n");
    exit(1);
}

$schema = [
    <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        google_access_token TEXT NULL,
        google_refresh_token TEXT NULL,
        google_token_expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<SQL
    CREATE TABLE IF NOT EXISTS availability_slots (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        duration_minutes INT NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        memo TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slots_start (start_datetime),
        INDEX idx_slots_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
    <<<SQL
    CREATE TABLE IF NOT EXISTS bookings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        availability_slot_id BIGINT UNSIGNED NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        company_name VARCHAR(255) NULL,
        client_email VARCHAR(255) NOT NULL,
        client_phone VARCHAR(255) NOT NULL,
        message TEXT NULL,
        booked_start_datetime DATETIME NOT NULL,
        booked_end_datetime DATETIME NOT NULL,
        google_event_id VARCHAR(255) NULL,
        google_meet_url TEXT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'confirmed',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_booking_slot (availability_slot_id),
        INDEX idx_bookings_status (status),
        INDEX idx_bookings_start (booked_start_datetime),
        CONSTRAINT fk_bookings_slot FOREIGN KEY (availability_slot_id) REFERENCES availability_slots(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL,
];

foreach ($schema as $sql) {
    $pdo->exec($sql);
}

$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
$adminName = getenv('ADMIN_NAME') ?: 'Admin';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'password123';
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

$statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$statement->execute(['email' => $adminEmail]);
$existing = $statement->fetchColumn();

if ($existing) {
    $update = $pdo->prepare(
        'UPDATE users SET name = :name, password_hash = :password_hash, updated_at = NOW() WHERE id = :id'
    );
    $update->execute([
        'id' => $existing,
        'name' => $adminName,
        'password_hash' => $adminHash,
    ]);
} else {
    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, created_at, updated_at) VALUES (:name, :email, :password_hash, NOW(), NOW())'
    );
    $insert->execute([
        'name' => $adminName,
        'email' => $adminEmail,
        'password_hash' => $adminHash,
    ]);
}

fwrite(STDOUT, "Setup completed.\n");

