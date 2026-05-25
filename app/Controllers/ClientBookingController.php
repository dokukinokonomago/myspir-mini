<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Services\BookingService;

class ClientBookingController
{
    private BookingService $bookingService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
    }

    public function index(): void
    {
        $statement = Database::connection()->query(
            "SELECT s.*
             FROM availability_slots s
             WHERE s.is_active = 1
               AND s.start_datetime >= NOW()
               AND NOT EXISTS (
                    SELECT 1
                    FROM bookings booked
                    WHERE booked.availability_slot_id = s.id
               )
               AND NOT EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.status = 'confirmed'
                      AND b.booked_start_datetime < s.end_datetime
                      AND b.booked_end_datetime > s.start_datetime
               )
             ORDER BY s.start_datetime ASC"
        );

        view('client/slots', [
            'pageTitle' => '予約日時を選択',
            'slots' => $statement->fetchAll(),
        ]);
    }

    public function showForm(string $id): void
    {
        $slot = $this->findBookableSlot((int) $id);

        if (!$slot) {
            Session::flash('error', '選択した予約枠は利用できません。');
            redirect('/book');
        }

        view('client/form', [
            'pageTitle' => 'お客様情報入力',
            'slot' => $slot,
            'form' => [
                'availability_slot_id' => $slot['id'],
                'client_name' => '',
                'company_name' => '',
                'client_email' => '',
                'client_phone' => '',
                'message' => '',
            ],
        ]);
    }

    public function confirm(): void
    {
        $form = $this->bookingFormFromRequest();
        $errors = $this->validateBookingForm($form);
        $slot = $this->findBookableSlot((int) $form['availability_slot_id']);

        if (!$slot) {
            $errors[] = '選択した予約枠は利用できません。';
        }

        if ($errors) {
            view('client/form', [
                'pageTitle' => 'お客様情報入力',
                'slot' => $slot,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        view('client/confirm', [
            'pageTitle' => '予約確認',
            'slot' => $slot,
            'form' => $form,
        ]);
    }

    public function store(): void
    {
        $form = $this->bookingFormFromRequest();
        $errors = $this->validateBookingForm($form);
        $slot = $this->findBookableSlot((int) $form['availability_slot_id']);

        if (!$slot) {
            $errors[] = '選択した予約枠は利用できません。';
        }

        if ($errors) {
            view('client/form', [
                'pageTitle' => 'お客様情報入力',
                'slot' => $slot,
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $pdo = Database::connection();
        $user = Auth::user() ?: $pdo->query('SELECT * FROM users ORDER BY id ASC LIMIT 1')->fetch();
        try {
            if (!$user) {
                throw new \RuntimeException('管理者ユーザーが見つかりません。');
            }

            $result = $this->bookingService->createConfirmedBooking(
                $user,
                (int) $form['availability_slot_id'],
                $form,
                true
            );

            Session::put('completed_booking_id', (int) $result['booking_id']);
            redirect('/book/complete');
        } catch (\Throwable $exception) {
            view('client/form', [
                'pageTitle' => 'お客様情報入力',
                'slot' => $slot,
                'errors' => [$exception->getMessage()],
                'form' => $form,
            ]);
        }
    }

    public function complete(): void
    {
        $bookingId = (int) Session::get('completed_booking_id', 0);

        if ($bookingId <= 0) {
            redirect('/book');
        }

        $statement = Database::connection()->prepare(
            "SELECT b.*, s.duration_minutes
             FROM bookings b
             INNER JOIN availability_slots s ON s.id = b.availability_slot_id
             WHERE b.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch();

        if (!$booking) {
            redirect('/book');
        }

        view('client/complete', [
            'pageTitle' => '予約完了',
            'booking' => $booking,
        ]);
    }

    private function bookingFormFromRequest(): array
    {
        return [
            'availability_slot_id' => (string) ($_POST['availability_slot_id'] ?? ''),
            'client_name' => trim((string) ($_POST['client_name'] ?? '')),
            'company_name' => trim((string) ($_POST['company_name'] ?? '')),
            'client_email' => trim((string) ($_POST['client_email'] ?? '')),
            'client_phone' => trim((string) ($_POST['client_phone'] ?? '')),
            'message' => trim((string) ($_POST['message'] ?? '')),
        ];
    }

    private function validateBookingForm(array $form): array
    {
        $errors = [];

        if ($form['availability_slot_id'] === '') {
            $errors[] = '予約枠を選択してください。';
        }
        if ($form['client_name'] === '') {
            $errors[] = 'お名前は必須です。';
        }
        if ($form['client_email'] === '' || !filter_var($form['client_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = '有効なメールアドレスを入力してください。';
        }
        if ($form['client_phone'] === '') {
            $errors[] = '電話番号は必須です。';
        }

        return $errors;
    }

    private function findBookableSlot(int $slotId): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT s.*
             FROM availability_slots s
             WHERE s.id = :id
               AND s.is_active = 1
               AND s.start_datetime >= NOW()
               AND NOT EXISTS (
                    SELECT 1
                    FROM bookings booked
                    WHERE booked.availability_slot_id = s.id
               )
               AND NOT EXISTS (
                    SELECT 1
                    FROM bookings b
                    WHERE b.status = 'confirmed'
                      AND b.booked_start_datetime < s.end_datetime
                      AND b.booked_end_datetime > s.start_datetime
               )
             LIMIT 1"
        );
        $statement->execute(['id' => $slotId]);
        $slot = $statement->fetch();

        return $slot ?: null;
    }
}
