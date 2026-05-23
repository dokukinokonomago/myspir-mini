<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use DateTimeImmutable;

class AvailabilityController
{
    public function index(): void
    {
        $statement = Database::connection()->query(
            "SELECT s.*,
                    b.id AS booking_id,
                    b.client_name,
                    b.client_email
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             ORDER BY s.start_datetime ASC"
        );

        view('admin/availabilities/index', [
            'pageTitle' => '空き枠一覧',
            'slots' => $statement->fetchAll(),
            'isAdminArea' => true,
        ]);
    }

    public function create(): void
    {
        view('admin/availabilities/create', [
            'pageTitle' => '空き枠追加',
            'isAdminArea' => true,
            'form' => [
                'date' => '',
                'start_time' => '',
                'end_time' => '',
                'duration_minutes' => '30',
                'is_active' => '1',
                'memo' => '',
            ],
        ]);
    }

    public function store(): void
    {
        $form = [
            'date' => trim((string) ($_POST['date'] ?? '')),
            'start_time' => trim((string) ($_POST['start_time'] ?? '')),
            'end_time' => trim((string) ($_POST['end_time'] ?? '')),
            'duration_minutes' => trim((string) ($_POST['duration_minutes'] ?? '30')),
            'is_active' => isset($_POST['is_active']) ? '1' : '0',
            'memo' => trim((string) ($_POST['memo'] ?? '')),
        ];

        $errors = [];

        if ($form['date'] === '' || $form['start_time'] === '' || $form['end_time'] === '') {
            $errors[] = '日付・開始時間・終了時間は必須です。';
        }

        if (!in_array((int) $form['duration_minutes'], [30, 60], true)) {
            $errors[] = '面談時間は 30 分または 60 分を選択してください。';
        }

        $start = null;
        $end = null;

        if (!$errors) {
            $start = new DateTimeImmutable($form['date'] . ' ' . $form['start_time']);
            $end = new DateTimeImmutable($form['date'] . ' ' . $form['end_time']);

            if ($end <= $start) {
                $errors[] = '終了時間は開始時間より後にしてください。';
            }
        }

        if (!$errors) {
            $conflict = Database::connection()->prepare(
                "SELECT id
                 FROM bookings
                 WHERE status = 'confirmed'
                   AND booked_start_datetime < :end
                   AND booked_end_datetime > :start
                 LIMIT 1"
            );
            $conflict->execute([
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ]);

            if ($conflict->fetch()) {
                $errors[] = 'この時間帯には既存予約があるため、空き枠を追加できません。';
            }
        }

        if ($errors) {
            view('admin/availabilities/create', [
                'pageTitle' => '空き枠追加',
                'isAdminArea' => true,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO availability_slots (start_datetime, end_datetime, duration_minutes, is_active, memo, created_at, updated_at)
             VALUES (:start_datetime, :end_datetime, :duration_minutes, :is_active, :memo, NOW(), NOW())'
        );

        $statement->execute([
            'start_datetime' => $start?->format('Y-m-d H:i:s'),
            'end_datetime' => $end?->format('Y-m-d H:i:s'),
            'duration_minutes' => (int) $form['duration_minutes'],
            'is_active' => (int) $form['is_active'],
            'memo' => $form['memo'] ?: null,
        ]);

        Session::flash('success', '空き枠を追加しました。');
        redirect('/admin/availability-slots');
    }

    public function toggle(string $id): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE availability_slots SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = :id'
        );
        $statement->execute(['id' => (int) $id]);

        Session::flash('success', '空き枠の表示状態を更新しました。');
        redirect('/admin/availability-slots');
    }
}

