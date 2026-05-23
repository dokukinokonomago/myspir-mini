<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

class BookingAdminController
{
    public function index(): void
    {
        $statement = Database::connection()->query(
            "SELECT b.*, s.memo, s.duration_minutes
             FROM bookings b
             INNER JOIN availability_slots s ON s.id = b.availability_slot_id
             ORDER BY b.booked_start_datetime DESC"
        );

        view('admin/bookings/index', [
            'pageTitle' => '予約一覧',
            'bookings' => $statement->fetchAll(),
            'isAdminArea' => true,
        ]);
    }

    public function show(string $id): void
    {
        $statement = Database::connection()->prepare(
            "SELECT b.*, s.memo, s.duration_minutes
             FROM bookings b
             INNER JOIN availability_slots s ON s.id = b.availability_slot_id
             WHERE b.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => (int) $id]);
        $booking = $statement->fetch();

        if (!$booking) {
            http_response_code(404);
            echo '予約が見つかりません。';
            return;
        }

        view('admin/bookings/show', [
            'pageTitle' => '予約詳細',
            'booking' => $booking,
            'isAdminArea' => true,
        ]);
    }
}

