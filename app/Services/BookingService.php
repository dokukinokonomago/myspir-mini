<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class BookingService
{
    private GoogleCalendarService $googleService;

    public function __construct()
    {
        $this->googleService = new GoogleCalendarService();
    }

    public function createConfirmedBooking(array $user, int $slotId, array $form, bool $requireActiveSlot = true): array
    {
        $pdo = Database::connection();
        $createdEventId = null;

        try {
            $pdo->beginTransaction();

            $slotSql = 'SELECT * FROM availability_slots WHERE id = :id';
            if ($requireActiveSlot) {
                $slotSql .= ' AND is_active = 1';
            }
            $slotSql .= ' LIMIT 1 FOR UPDATE';

            $slotStatement = $pdo->prepare($slotSql);
            $slotStatement->execute(['id' => $slotId]);
            $lockedSlot = $slotStatement->fetch(PDO::FETCH_ASSOC);

            if (!$lockedSlot) {
                throw new \RuntimeException('選択した予約枠は現在利用できません。');
            }

            if ((new \DateTimeImmutable($lockedSlot['end_datetime'])) <= new \DateTimeImmutable()) {
                throw new \RuntimeException('過去の予約枠は予約済みにできません。');
            }

            $conflictStatement = $pdo->prepare(
                "SELECT id
                 FROM bookings
                 WHERE status = 'confirmed'
                   AND booked_start_datetime < :end
                   AND booked_end_datetime > :start
                 LIMIT 1 FOR UPDATE"
            );
            $conflictStatement->execute([
                'start' => $lockedSlot['start_datetime'],
                'end' => $lockedSlot['end_datetime'],
            ]);

            if ($conflictStatement->fetch()) {
                throw new \RuntimeException('この時間帯はすでに予約済みです。別の枠を選択してください。');
            }

            $bookingPayload = array_merge($form, [
                'booked_start_datetime' => $lockedSlot['start_datetime'],
                'booked_end_datetime' => $lockedSlot['end_datetime'],
            ]);

            $googleEvent = $this->googleService->createEventWithMeet($user, $bookingPayload);
            $createdEventId = $googleEvent['event_id'];

            $insert = $pdo->prepare(
                'INSERT INTO bookings (
                    availability_slot_id,
                    client_name,
                    company_name,
                    client_email,
                    client_phone,
                    message,
                    booked_start_datetime,
                    booked_end_datetime,
                    google_event_id,
                    google_meet_url,
                    status,
                    created_at,
                    updated_at
                 ) VALUES (
                    :availability_slot_id,
                    :client_name,
                    :company_name,
                    :client_email,
                    :client_phone,
                    :message,
                    :booked_start_datetime,
                    :booked_end_datetime,
                    :google_event_id,
                    :google_meet_url,
                    :status,
                    NOW(),
                    NOW()
                 )'
            );

            $insert->execute([
                'availability_slot_id' => (int) $lockedSlot['id'],
                'client_name' => $form['client_name'],
                'company_name' => $form['company_name'] ?: null,
                'client_email' => $form['client_email'],
                'client_phone' => $form['client_phone'],
                'message' => $form['message'] ?: null,
                'booked_start_datetime' => $lockedSlot['start_datetime'],
                'booked_end_datetime' => $lockedSlot['end_datetime'],
                'google_event_id' => $googleEvent['event_id'],
                'google_meet_url' => $googleEvent['meet_url'],
                'status' => 'confirmed',
            ]);

            $bookingId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return [
                'booking_id' => $bookingId,
                'event_id' => $googleEvent['event_id'],
                'meet_url' => $googleEvent['meet_url'],
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($createdEventId) {
                $this->googleService->deleteEvent($user, $createdEventId);
            }

            throw $exception;
        }
    }
}
