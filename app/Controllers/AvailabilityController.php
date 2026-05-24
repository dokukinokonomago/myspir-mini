<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use DateTimeImmutable;
use PDO;

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
        $calendar = $this->buildCalendarState(
            $this->resolveCalendarReferenceDate($_GET['week'] ?? null)
        );

        view('admin/availabilities/create', [
            'pageTitle' => '空き枠追加',
            'isAdminArea' => true,
            'calendar' => $calendar,
            'form' => [
                'date' => '',
                'start_time' => '',
                'end_time' => '',
                'duration_minutes' => '30',
                'recurrence_type' => 'single',
                'recurrence_count' => '1',
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
            'recurrence_type' => trim((string) ($_POST['recurrence_type'] ?? 'single')),
            'recurrence_count' => trim((string) ($_POST['recurrence_count'] ?? '1')),
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

        if (!in_array($form['recurrence_type'], ['single', 'weekly', 'monthly'], true)) {
            $errors[] = '繰り返し設定が不正です。';
        }

        if ($form['recurrence_type'] === 'single') {
            $form['recurrence_count'] = '1';
        }

        $recurrenceCount = (int) $form['recurrence_count'];

        if ($recurrenceCount < 1 || $recurrenceCount > 24) {
            $errors[] = '作成回数は 1 回から 24 回で指定してください。';
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

        $slotsToCreate = [];

        if (!$errors && $start && $end) {
            $pdo = Database::connection();

            for ($index = 0; $index < $recurrenceCount; $index++) {
                $occurrenceStart = $this->shiftDateTime($start, $form['recurrence_type'], $index);
                $occurrenceEnd = $this->shiftDateTime($end, $form['recurrence_type'], $index);

                if ($occurrenceEnd <= $occurrenceStart) {
                    $errors[] = '繰り返し後の終了日時が不正です。';
                    break;
                }

                $conflictMessage = $this->findConflictMessage($pdo, $occurrenceStart, $occurrenceEnd);

                if ($conflictMessage !== null) {
                    $errors[] = $conflictMessage;
                } else {
                    $slotsToCreate[] = [
                        'start_datetime' => $occurrenceStart->format('Y-m-d H:i:s'),
                        'end_datetime' => $occurrenceEnd->format('Y-m-d H:i:s'),
                    ];
                }
            }

            if ($this->hasInternalOverlap($slotsToCreate)) {
                $errors[] = '指定した繰り返し設定だと、作成候補どうしが重複します。';
            }
        }

        if ($errors) {
            $referenceDate = $form['date'] !== ''
                ? $this->resolveCalendarReferenceDate($form['date'])
                : $this->resolveCalendarReferenceDate($_GET['week'] ?? null);

            view('admin/availabilities/create', [
                'pageTitle' => '空き枠追加',
                'isAdminArea' => true,
                'calendar' => $this->buildCalendarState($referenceDate),
                'errors' => $errors,
                'form' => $form,
            ]);
            return;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO availability_slots (start_datetime, end_datetime, duration_minutes, is_active, memo, created_at, updated_at)
                 VALUES (:start_datetime, :end_datetime, :duration_minutes, :is_active, :memo, NOW(), NOW())'
            );

            foreach ($slotsToCreate as $slot) {
                $statement->execute([
                    'start_datetime' => $slot['start_datetime'],
                    'end_datetime' => $slot['end_datetime'],
                    'duration_minutes' => (int) $form['duration_minutes'],
                    'is_active' => (int) $form['is_active'],
                    'memo' => $form['memo'] ?: null,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        $createdCount = count($slotsToCreate);
        Session::flash('success', $createdCount . '件の空き枠を追加しました。');
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

    private function shiftDateTime(DateTimeImmutable $dateTime, string $recurrenceType, int $offset): DateTimeImmutable
    {
        if ($offset === 0 || $recurrenceType === 'single') {
            return $dateTime;
        }

        if ($recurrenceType === 'weekly') {
            return $dateTime->modify('+' . $offset . ' week');
        }

        $year = (int) $dateTime->format('Y');
        $month = (int) $dateTime->format('n');
        $day = (int) $dateTime->format('j');
        $hour = (int) $dateTime->format('H');
        $minute = (int) $dateTime->format('i');
        $second = (int) $dateTime->format('s');

        $targetMonth = $month + $offset;
        $targetYear = $year + intdiv($targetMonth - 1, 12);
        $targetMonth = (($targetMonth - 1) % 12) + 1;
        $lastDay = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $targetYear, $targetMonth)))->format('t');
        $targetDay = min($day, $lastDay);

        return (new DateTimeImmutable())
            ->setDate($targetYear, $targetMonth, $targetDay)
            ->setTime($hour, $minute, $second);
    }

    private function findConflictMessage(PDO $pdo, DateTimeImmutable $start, DateTimeImmutable $end): ?string
    {
        $bookingConflict = $pdo->prepare(
            "SELECT id
             FROM bookings
             WHERE status = 'confirmed'
               AND booked_start_datetime < :end
               AND booked_end_datetime > :start
             LIMIT 1"
        );
        $bookingConflict->execute([
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        if ($bookingConflict->fetch()) {
            return $start->format('Y/m/d H:i') . ' は既存予約と重なるため追加できません。';
        }

        $slotConflict = $pdo->prepare(
            "SELECT id
             FROM availability_slots
             WHERE start_datetime < :end
               AND end_datetime > :start
             LIMIT 1"
        );
        $slotConflict->execute([
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        if ($slotConflict->fetch()) {
            return $start->format('Y/m/d H:i') . ' は既存の空き枠と重なるため追加できません。';
        }

        return null;
    }

    private function hasInternalOverlap(array $slots): bool
    {
        usort($slots, static fn (array $left, array $right): int => strcmp($left['start_datetime'], $right['start_datetime']));

        $previousEnd = null;

        foreach ($slots as $slot) {
            if ($previousEnd !== null && $slot['start_datetime'] < $previousEnd) {
                return true;
            }

            $previousEnd = $slot['end_datetime'];
        }

        return false;
    }

    private function resolveCalendarReferenceDate(null|string $rawDate): DateTimeImmutable
    {
        if (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) === 1) {
            try {
                return new DateTimeImmutable($rawDate . ' 00:00:00');
            } catch (\Throwable) {
            }
        }

        return new DateTimeImmutable('today');
    }

    private function buildCalendarState(DateTimeImmutable $referenceDate): array
    {
        $weekStart = $referenceDate->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+7 days');
        $dayMap = [];
        $days = [];

        for ($index = 0; $index < 7; $index++) {
            $day = $weekStart->modify('+' . $index . ' day');
            $dateKey = $day->format('Y-m-d');
            $dayMap[$dateKey] = $index;
            $days[] = [
                'index' => $index,
                'date' => $dateKey,
                'weekday_short' => $day->format('D'),
                'label' => $day->format('n/j'),
                'is_today' => $day->format('Y-m-d') === (new DateTimeImmutable('today'))->format('Y-m-d'),
            ];
        }

        $statement = Database::connection()->prepare(
            "SELECT s.*,
                    b.id AS booking_id,
                    b.client_name,
                    b.client_email
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.start_datetime < :week_end
               AND s.end_datetime > :week_start
             ORDER BY s.start_datetime ASC"
        );
        $statement->execute([
            'week_start' => $weekStart->format('Y-m-d H:i:s'),
            'week_end' => $weekEnd->format('Y-m-d H:i:s'),
        ]);

        $slotEvents = [];
        $weekStats = [
            'available' => 0,
            'booked' => 0,
            'hidden' => 0,
        ];

        foreach ($statement->fetchAll() as $row) {
            $start = new DateTimeImmutable($row['start_datetime']);
            $dateKey = $start->format('Y-m-d');

            if (!isset($dayMap[$dateKey])) {
                continue;
            }

            $status = $row['booking_id']
                ? 'booked'
                : ((int) $row['is_active'] === 1 ? 'available' : 'hidden');

            $weekStats[$status]++;

            $slotEvents[] = [
                'id' => (int) $row['id'],
                'day_index' => $dayMap[$dateKey],
                'date' => $dateKey,
                'start_time' => $start->format('H:i'),
                'end_time' => (new DateTimeImmutable($row['end_datetime']))->format('H:i'),
                'duration_minutes' => (int) $row['duration_minutes'],
                'memo' => $row['memo'] ?: '',
                'status' => $status,
                'booking_id' => $row['booking_id'] ? (int) $row['booking_id'] : null,
                'client_name' => $row['client_name'] ?: '',
            ];
        }

        return [
            'week_start' => $weekStart->format('Y-m-d'),
            'week_start_label' => $weekStart->format('Y年n月j日'),
            'week_end_label' => $weekStart->modify('+6 days')->format('n月j日'),
            'prev_week' => $weekStart->modify('-7 days')->format('Y-m-d'),
            'next_week' => $weekStart->modify('+7 days')->format('Y-m-d'),
            'days' => $days,
            'slot_events' => $slotEvents,
            'stats' => $weekStats,
            'time_start_hour' => 8,
            'time_end_hour' => 21,
            'slot_step_minutes' => 30,
        ];
    }
}
