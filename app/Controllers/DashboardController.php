<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;

class DashboardController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $user = Auth::user();

        $stats = [
            'slots_total' => (int) $pdo->query('SELECT COUNT(*) FROM availability_slots')->fetchColumn(),
            'slots_active' => (int) $pdo->query('SELECT COUNT(*) FROM availability_slots WHERE is_active = 1')->fetchColumn(),
            'bookings_total' => (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
        ];

        $upcomingStatement = $pdo->query(
            "SELECT b.*, s.memo
             FROM bookings b
             INNER JOIN availability_slots s ON s.id = b.availability_slot_id
             WHERE b.status = 'confirmed' AND b.booked_start_datetime >= NOW()
             ORDER BY b.booked_start_datetime ASC
             LIMIT 5"
        );

        view('admin/dashboard', [
            'pageTitle' => 'ダッシュボード',
            'user' => $user,
            'stats' => $stats,
            'upcomingBookings' => $upcomingStatement->fetchAll(),
            'googleConnected' => !empty($user['google_access_token']) && !empty($user['google_refresh_token']),
            'isAdminArea' => true,
        ]);
    }
}

