<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Services\BookingService;
use App\Services\GoogleCalendarService;
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
                'segments_json' => '',
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
            'segments_json' => trim((string) ($_POST['segments_json'] ?? '')),
            'recurrence_type' => trim((string) ($_POST['recurrence_type'] ?? 'single')),
            'recurrence_count' => trim((string) ($_POST['recurrence_count'] ?? '1')),
            'is_active' => isset($_POST['is_active']) ? '1' : '0',
            'memo' => trim((string) ($_POST['memo'] ?? '')),
        ];

        $errors = [];

        if ($form['date'] === '' || $form['start_time'] === '' || $form['end_time'] === '') {
            $errors[] = '日付・開始時間・終了時間は必須です。';
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

        $segments = $this->extractSegmentsFromForm($form, $errors);
        $slotsToCreate = [];

        if (!$errors && $segments !== []) {
            $pdo = Database::connection();

            for ($index = 0; $index < $recurrenceCount; $index++) {
                foreach ($segments as $segment) {
                    $segmentStart = new DateTimeImmutable($segment['date'] . ' ' . $segment['start_time']);
                    $segmentEnd = new DateTimeImmutable($segment['date'] . ' ' . $segment['end_time']);

                    $occurrenceStart = $this->shiftDateTime($segmentStart, $form['recurrence_type'], $index);
                    $occurrenceEnd = $this->shiftDateTime($segmentEnd, $form['recurrence_type'], $index);

                    if ($occurrenceEnd <= $occurrenceStart) {
                        $errors[] = '繰り返し後の終了日時が不正です。';
                        break 2;
                    }

                    $conflictMessage = $this->findConflictMessage($pdo, $occurrenceStart, $occurrenceEnd);

                    if ($conflictMessage !== null) {
                        $errors[] = $conflictMessage;
                    } else {
                        $slotsToCreate[] = [
                            'start_datetime' => $occurrenceStart->format('Y-m-d H:i:s'),
                            'end_datetime' => $occurrenceEnd->format('Y-m-d H:i:s'),
                            'duration_minutes' => (int) $segment['duration_minutes'],
                            'is_active' => (int) $segment['is_active'],
                            'memo' => $segment['memo'] !== '' ? $segment['memo'] : null,
                        ];
                    }
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
                    'duration_minutes' => (int) $slot['duration_minutes'],
                    'is_active' => (int) $slot['is_active'],
                    'memo' => $slot['memo'],
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

    public function destroy(string $id): void
    {
        $this->deleteSlotAndRedirect((int) $id);
    }

    public function destroyFromRequest(): void
    {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $this->deleteSlotAndRedirect($slotId);
    }

    public function bulkDestroy(): void
    {
        $date = trim((string) ($_POST['date'] ?? ''));
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));

        if ($date === '' || $startTime === '' || $endTime === '') {
            Session::flash('error', '削除範囲の日時が不足しています。');
            redirect('/admin/availability-slots/create');
        }

        try {
            $rangeStart = new DateTimeImmutable($date . ' ' . $startTime);
            $rangeEnd = new DateTimeImmutable($date . ' ' . $endTime);
        } catch (\Throwable) {
            Session::flash('error', '削除範囲の日時形式が不正です。');
            redirect('/admin/availability-slots/create');
        }

        if ($rangeEnd <= $rangeStart) {
            Session::flash('error', '削除範囲の終了時間は開始時間より後にしてください。');
            redirect('/admin/availability-slots/create?week=' . $rangeStart->format('Y-m-d'));
        }

        $pdo = Database::connection();

        $deletableCount = $this->countRangeSlots($pdo, $rangeStart, $rangeEnd, true);
        $bookedCount = $this->countRangeSlots($pdo, $rangeStart, $rangeEnd, false);

        if ($deletableCount === 0) {
            $message = $bookedCount > 0
                ? '選択範囲には予約済みの空き枠のみが含まれているため削除できません。'
                : '選択範囲に削除できる空き枠がありません。';
            Session::flash('error', $message);
            redirect('/admin/availability-slots/create?week=' . $rangeStart->format('Y-m-d'));
        }

        $delete = $pdo->prepare(
            "DELETE s
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.start_datetime >= :range_start
               AND s.end_datetime <= :range_end
               AND b.id IS NULL"
        );
        $delete->execute([
            'range_start' => $rangeStart->format('Y-m-d H:i:s'),
            'range_end' => $rangeEnd->format('Y-m-d H:i:s'),
        ]);

        $message = $deletableCount . '件の空き枠を削除しました。';
        if ($bookedCount > 0) {
            $message .= ' 予約済み ' . $bookedCount . ' 件は削除していません。';
        }

        Session::flash('success', $message);
        redirect('/admin/availability-slots/create?week=' . $rangeStart->format('Y-m-d'));
    }

    public function destroySelected(): void
    {
        $slotIds = $this->selectedSlotIdsFromRequest();

        if ($slotIds === []) {
            Session::flash('error', '削除する空き枠を選択してください。');
            redirect('/admin/availability-slots');
        }

        $placeholders = implode(', ', array_fill(0, count($slotIds), '?'));
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            "SELECT s.id, b.id AS booking_id
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.id IN ({$placeholders})"
        );
        $statement->execute($slotIds);

        $deletableIds = [];
        $bookedCount = 0;

        foreach ($statement->fetchAll() as $slot) {
            if ($slot['booking_id']) {
                $bookedCount++;
                continue;
            }

            $deletableIds[] = (int) $slot['id'];
        }

        if ($deletableIds === []) {
            $message = $bookedCount > 0
                ? '選択した空き枠はすべて予約済みのため削除できません。'
                : '削除できる空き枠が見つかりませんでした。';
            Session::flash('error', $message);
            redirect('/admin/availability-slots');
        }

        $deletePlaceholders = implode(', ', array_fill(0, count($deletableIds), '?'));
        $delete = $pdo->prepare("DELETE FROM availability_slots WHERE id IN ({$deletePlaceholders})");
        $delete->execute($deletableIds);

        $message = count($deletableIds) . '件の空き枠を削除しました。';
        if ($bookedCount > 0) {
            $message .= ' 予約済み ' . $bookedCount . ' 件は削除していません。';
        }

        Session::flash('success', $message);
        redirect('/admin/availability-slots');
    }

    public function hideSelected(): void
    {
        $slotIds = $this->selectedSlotIdsFromRequest();

        if ($slotIds === []) {
            Session::flash('error', '非表示にする空き枠を選択してください。');
            redirect('/admin/availability-slots');
        }

        $placeholders = implode(', ', array_fill(0, count($slotIds), '?'));
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            "SELECT s.id, s.is_active, b.id AS booking_id
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.id IN ({$placeholders})"
        );
        $statement->execute($slotIds);

        $targetIds = [];
        $bookedCount = 0;
        $alreadyHiddenCount = 0;

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $slot) {
            if ($slot['booking_id']) {
                $bookedCount++;
                continue;
            }

            if ((int) $slot['is_active'] !== 1) {
                $alreadyHiddenCount++;
                continue;
            }

            $targetIds[] = (int) $slot['id'];
        }

        if ($targetIds === []) {
            $message = $bookedCount > 0
                ? '選択した空き枠は予約済み、またはすでに非表示です。'
                : '非表示にできる空き枠が見つかりませんでした。';
            Session::flash('error', $message);
            redirect('/admin/availability-slots');
        }

        $updatePlaceholders = implode(', ', array_fill(0, count($targetIds), '?'));
        $update = $pdo->prepare("UPDATE availability_slots SET is_active = 0, updated_at = NOW() WHERE id IN ({$updatePlaceholders})");
        $update->execute($targetIds);

        $message = count($targetIds) . '件の空き枠を非表示にしました。';
        if ($alreadyHiddenCount > 0) {
            $message .= ' すでに非表示 ' . $alreadyHiddenCount . ' 件は変更していません。';
        }
        if ($bookedCount > 0) {
            $message .= ' 予約済み ' . $bookedCount . ' 件は変更していません。';
        }

        Session::flash('success', $message);
        redirect('/admin/availability-slots');
    }

    public function reserveSelected(): void
    {
        $reservations = $this->selectedReservationsFromRequest();
        $slotIds = array_values(array_unique(array_map(
            static fn (array $reservation): int => (int) $reservation['slot_id'],
            $reservations
        )));

        $user = Auth::user();
        if (!$user) {
            Session::flash('error', '管理者情報を取得できません。');
            redirect('/admin/login');
        }

        if ($reservations === [] || $slotIds === []) {
            Session::flash('error', '予約済みにする空き枠を選択してください。');
            redirect('/admin/availability-slots');
        }

        $placeholders = implode(', ', array_fill(0, count($slotIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT s.id, s.memo, s.is_active, s.start_datetime, s.end_datetime, b.id AS booking_id
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.id IN ({$placeholders})
             ORDER BY s.start_datetime ASC"
        );
        $statement->execute($slotIds);

        $slotMap = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $slot) {
            $slotMap[(int) $slot['id']] = $slot;
        }

        $successCount = 0;
        $failedMessages = [];
        $bookedCount = 0;
        $updateSlot = Database::connection()->prepare(
            'UPDATE availability_slots SET memo = :memo, is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );

        foreach ($reservations as $reservation) {
            $slotId = (int) $reservation['slot_id'];
            $slot = $slotMap[$slotId] ?? null;

            if (!$slot) {
                $failedMessages[] = '対象の空き枠が見つかりません。';
                continue;
            }

            if ($slot['booking_id']) {
                $bookedCount++;
                continue;
            }

            if ($reservation['client_name'] === '') {
                $failedMessages[] = format_datetime($slot['start_datetime']) . ' の氏名を入力してください。';
                continue;
            }

            if ($reservation['client_email'] !== '' && !filter_var($reservation['client_email'], FILTER_VALIDATE_EMAIL)) {
                $failedMessages[] = format_datetime($slot['start_datetime']) . ' のメールアドレス形式が不正です。';
                continue;
            }

            $updateSlot->execute([
                'id' => $slotId,
                'memo' => $reservation['slot_memo'] !== '' ? $reservation['slot_memo'] : null,
                'is_active' => $reservation['is_active'],
            ]);

            try {
                $booking = [
                    'client_name' => $reservation['client_name'],
                    'company_name' => $reservation['company_name'],
                    'client_email' => $reservation['client_email'],
                    'client_phone' => $reservation['client_phone'] !== '' ? $reservation['client_phone'] : '-',
                    'message' => $reservation['message'],
                ];
                if ($booking['client_email'] === '') {
                    $booking['client_email'] = 'manual-booking+' . $slotId . '@local.invalid';
                }
                (new BookingService())->createConfirmedBooking($user, $slotId, $booking, false);
                $successCount++;
            } catch (\Throwable $exception) {
                $failedMessages[] = $exception->getMessage();
            }
        }

        if ($successCount === 0) {
            Session::flash('error', $failedMessages[0] ?? '予約済みへの変更に失敗しました。');
            redirect('/admin/availability-slots');
        }

        $message = $successCount . '件の空き枠を予約済みにしました。';
        if ($bookedCount > 0) {
            $message .= ' すでに予約済み ' . $bookedCount . ' 件は変更していません。';
        }
        if ($failedMessages !== []) {
            $message .= ' 一部失敗: ' . $failedMessages[0];
        }

        Session::flash('success', $message);
        redirect('/admin/availability-slots');
    }

    public function updateDetails(): void
    {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $week = trim((string) ($_POST['week'] ?? ''));
        $form = $this->slotDetailFormFromRequest();

        if ($slotId <= 0) {
            Session::flash('error', '対象のスケジュールが見つかりません。');
            redirect('/admin/availability-slots/create' . $this->buildWeekQuery($week));
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            "SELECT s.id, s.memo, s.is_active, b.id AS booking_id
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $slotId]);
        $slot = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$slot) {
            Session::flash('error', '対象のスケジュールが見つかりません。');
            redirect('/admin/availability-slots/create' . $this->buildWeekQuery($week));
        }

        $pdo->beginTransaction();

        try {
            $updateSlot = $pdo->prepare(
                'UPDATE availability_slots
                 SET memo = :memo,
                     is_active = :is_active,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $updateSlot->execute([
                'id' => $slotId,
                'memo' => $form['slot_memo'] !== '' ? $form['slot_memo'] : null,
                'is_active' => $form['is_active'],
            ]);

            if ($slot['booking_id']) {
                $updateBooking = $pdo->prepare(
                    'UPDATE bookings
                     SET client_name = :client_name,
                         company_name = :company_name,
                         client_email = :client_email,
                         client_phone = :client_phone,
                         message = :message,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $updateBooking->execute([
                    'id' => (int) $slot['booking_id'],
                    'client_name' => $form['client_name'] !== '' ? $form['client_name'] : '予約あり',
                    'company_name' => $form['company_name'] !== '' ? $form['company_name'] : null,
                    'client_email' => $form['client_email'] !== '' ? $form['client_email'] : 'manual-booking+' . $slotId . '@local.invalid',
                    'client_phone' => $form['client_phone'] !== '' ? $form['client_phone'] : '-',
                    'message' => $form['message'] !== '' ? $form['message'] : null,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Session::flash('error', 'スケジュール詳細の更新に失敗しました。');
            redirect('/admin/availability-slots/create' . $this->buildWeekQuery($week));
        }

        Session::flash('success', 'スケジュール詳細を更新しました。');
        redirect('/admin/availability-slots/create' . $this->buildWeekQuery($week));
    }

    public function reserve(): void
    {
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $form = $this->slotDetailFormFromRequest();

        if ($slotId <= 0) {
            Session::flash('error', '対象のスケジュールが見つかりません。');
            redirect($this->reserveRedirectPath());
        }

        if ($form['client_name'] === '') {
            Session::flash('error', '予約済みにするには氏名を入力してください。');
            redirect($this->reserveRedirectPath());
        }

        if ($form['client_email'] !== '' && !filter_var($form['client_email'], FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'メールアドレスの形式が不正です。');
            redirect($this->reserveRedirectPath());
        }

        $user = Auth::user();
        if (!$user) {
            Session::flash('error', '管理者情報を取得できません。');
            redirect('/admin/login');
        }

        $normalizedBooking = [
            'client_name' => $form['client_name'],
            'company_name' => $form['company_name'],
            'client_email' => $form['client_email'] !== '' ? $form['client_email'] : 'manual-booking+' . $slotId . '@local.invalid',
            'client_phone' => $form['client_phone'] !== '' ? $form['client_phone'] : '-',
            'message' => $form['message'],
        ];

        $updateSlot = Database::connection()->prepare(
            'UPDATE availability_slots SET memo = :memo, is_active = :is_active, updated_at = NOW() WHERE id = :id'
        );
        $updateSlot->execute([
            'id' => $slotId,
            'memo' => $form['slot_memo'] !== '' ? $form['slot_memo'] : null,
            'is_active' => $form['is_active'],
        ]);

        try {
            $result = (new BookingService())->createConfirmedBooking($user, $slotId, $normalizedBooking, false);
            Session::flash('success', '予約済みに変更しました。Google Calendar と Google Meet も作成しています。');
            redirect('/admin/bookings/' . (int) $result['booking_id']);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            redirect($this->reserveRedirectPath());
        }
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

    private function extractSegmentsFromForm(array $form, array &$errors): array
    {
        $rawSegments = [];

        if ($form['segments_json'] !== '') {
            $decoded = json_decode($form['segments_json'], true);

            if (!is_array($decoded)) {
                $errors[] = '空き枠の分割データを読み取れませんでした。';
                return [];
            }

            $rawSegments = $decoded;
        } elseif ($form['date'] !== '' && $form['start_time'] !== '' && $form['end_time'] !== '') {
            $rawSegments = [[
                'date' => $form['date'],
                'start_time' => $form['start_time'],
                'end_time' => $form['end_time'],
                'memo' => $form['memo'],
                'is_active' => $form['is_active'],
            ]];
        }

        if ($rawSegments === []) {
            $errors[] = '空き時間を選択してください。';
            return [];
        }

        $segments = [];

        foreach ($rawSegments as $index => $segment) {
            if (!is_array($segment)) {
                $errors[] = '空き枠データの形式が不正です。';
                continue;
            }

            $date = trim((string) ($segment['date'] ?? ''));
            $startTime = trim((string) ($segment['start_time'] ?? ''));
            $endTime = trim((string) ($segment['end_time'] ?? ''));
            $memo = trim((string) ($segment['memo'] ?? ''));
            $isActive = (string) ($segment['is_active'] ?? '1') === '1' ? 1 : 0;

            if ($date === '' || $startTime === '' || $endTime === '') {
                $errors[] = ($index + 1) . '件目の空き枠で日時が不足しています。';
                continue;
            }

            try {
                $start = new DateTimeImmutable($date . ' ' . $startTime);
                $end = new DateTimeImmutable($date . ' ' . $endTime);
            } catch (\Throwable) {
                $errors[] = ($index + 1) . '件目の日時形式が不正です。';
                continue;
            }

            if ($end <= $start) {
                $errors[] = ($index + 1) . '件目の終了時間は開始時間より後にしてください。';
                continue;
            }

            $durationMinutes = (int) (($end->getTimestamp() - $start->getTimestamp()) / 60);

            if ($durationMinutes < 30 || $durationMinutes % 30 !== 0) {
                $errors[] = ($index + 1) . '件目の空き枠は 30 分単位で指定してください。';
                continue;
            }

            $segments[] = [
                'date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'duration_minutes' => $durationMinutes,
                'memo' => $memo,
                'is_active' => $isActive,
            ];
        }

        usort($segments, static function (array $left, array $right): int {
            $leftKey = $left['date'] . ' ' . $left['start_time'];
            $rightKey = $right['date'] . ' ' . $right['start_time'];
            return strcmp($leftKey, $rightKey);
        });

        for ($index = 1, $count = count($segments); $index < $count; $index++) {
            $previous = $segments[$index - 1];
            $current = $segments[$index];

            if ($previous['date'] !== $current['date']) {
                continue;
            }

            if ($current['start_time'] < $previous['end_time']) {
                $errors[] = '分割した空き枠どうしが重複しています。';
                break;
            }
        }

        return $segments;
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
                    b.client_email,
                    b.client_phone,
                    b.company_name,
                    b.message,
                    b.google_event_id,
                    b.google_meet_url,
                    b.status AS booking_status
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
                'client_email' => $row['client_email'] ?: '',
                'client_phone' => $row['client_phone'] ?: '',
                'company_name' => $row['company_name'] ?: '',
                'message' => $row['message'] ?: '',
                'google_event_id' => $row['google_event_id'] ?: '',
                'google_meet_url' => $row['google_meet_url'] ?: '',
                'is_active' => (int) $row['is_active'] === 1,
                'can_delete' => true,
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
            'time_start_hour' => 6,
            'time_end_hour' => 23,
            'slot_step_minutes' => 30,
        ];
    }

    private function countRangeSlots(PDO $pdo, DateTimeImmutable $rangeStart, DateTimeImmutable $rangeEnd, bool $deletableOnly): int
    {
        $sql = $deletableOnly
            ? "SELECT COUNT(*)
               FROM availability_slots s
               LEFT JOIN bookings b ON b.availability_slot_id = s.id
               WHERE s.start_datetime >= :range_start
                 AND s.end_datetime <= :range_end
                 AND b.id IS NULL"
            : "SELECT COUNT(*)
               FROM availability_slots s
               INNER JOIN bookings b ON b.availability_slot_id = s.id
               WHERE s.start_datetime >= :range_start
                 AND s.end_datetime <= :range_end";

        $statement = $pdo->prepare($sql);
        $statement->execute([
            'range_start' => $rangeStart->format('Y-m-d H:i:s'),
            'range_end' => $rangeEnd->format('Y-m-d H:i:s'),
        ]);

        return (int) $statement->fetchColumn();
    }

    private function deleteSlotAndRedirect(int $slotId): void
    {
        if ($slotId <= 0) {
            Session::flash('error', '削除対象の空き枠が見つかりません。');
            redirect($this->destroyRedirectPath());
        }

        $pdo = Database::connection();
        $statement = $pdo->prepare(
            "SELECT s.id,
                    b.id AS booking_id,
                    b.google_event_id
             FROM availability_slots s
             LEFT JOIN bookings b ON b.availability_slot_id = s.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $slotId]);
        $slot = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$slot) {
            Session::flash('error', '削除対象の空き枠が見つかりません。');
            redirect($this->destroyRedirectPath());
        }

        if ($slot['booking_id']) {
            $user = Auth::user();
            if ($user && $slot['google_event_id']) {
                (new GoogleCalendarService())->deleteEvent($user, (string) $slot['google_event_id']);
            }

            $pdo->beginTransaction();

            try {
                $deleteBooking = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
                $deleteBooking->execute(['id' => (int) $slot['booking_id']]);

                $deleteSlot = $pdo->prepare('DELETE FROM availability_slots WHERE id = :id');
                $deleteSlot->execute(['id' => (int) $slot['id']]);

                $pdo->commit();
            } catch (\Throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                Session::flash('error', '予約済み予定の削除に失敗しました。');
                redirect($this->destroyRedirectPath());
            }

            Session::flash('success', '予約済み予定を削除しました。Google カレンダー上の予定も削除しています。');
            redirect($this->destroyRedirectPath());
        }

        $delete = $pdo->prepare('DELETE FROM availability_slots WHERE id = :id');
        $delete->execute(['id' => $slotId]);

        Session::flash('success', '空き枠を削除しました。');
        redirect($this->destroyRedirectPath());
    }

    private function destroyRedirectPath(): string
    {
        $returnTo = trim((string) ($_POST['return_to'] ?? 'index'));
        $week = trim((string) ($_POST['week'] ?? ''));

        if ($returnTo === 'create') {
            return '/admin/availability-slots/create' . $this->buildWeekQuery($week);
        }

        return '/admin/availability-slots';
    }

    private function reserveRedirectPath(): string
    {
        $returnTo = trim((string) ($_POST['return_to'] ?? 'create'));
        $week = trim((string) ($_POST['week'] ?? ''));

        if ($returnTo === 'index') {
            return '/admin/availability-slots';
        }

        return '/admin/availability-slots/create' . $this->buildWeekQuery($week);
    }

    private function selectedSlotIdsFromRequest(): array
    {
        $slotIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) ($_POST['slot_ids'] ?? [])
        )));

        return array_values(array_filter($slotIds, static fn (int $id): bool => $id > 0));
    }

    private function selectedReservationsFromRequest(): array
    {
        $payload = trim((string) ($_POST['bookings_payload_json'] ?? ''));

        if ($payload === '') {
            $form = $this->slotDetailFormFromRequest();

            return array_map(
                static fn (int $slotId): array => [
                    'slot_id' => $slotId,
                    'client_name' => $form['client_name'],
                    'company_name' => $form['company_name'],
                    'client_email' => $form['client_email'],
                    'client_phone' => $form['client_phone'],
                    'message' => $form['message'],
                    'slot_memo' => $form['slot_memo'],
                    'is_active' => $form['is_active'],
                ],
                $this->selectedSlotIdsFromRequest()
            );
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return [];
        }

        $reservations = [];
        foreach ($decoded as $reservation) {
            if (!is_array($reservation)) {
                continue;
            }

            $slotId = (int) ($reservation['slot_id'] ?? 0);
            if ($slotId <= 0) {
                continue;
            }

            $reservations[$slotId] = [
                'slot_id' => $slotId,
                'client_name' => trim((string) ($reservation['client_name'] ?? '')),
                'company_name' => trim((string) ($reservation['company_name'] ?? '')),
                'client_email' => trim((string) ($reservation['client_email'] ?? '')),
                'client_phone' => trim((string) ($reservation['client_phone'] ?? '')),
                'message' => trim((string) ($reservation['message'] ?? '')),
                'slot_memo' => trim((string) ($reservation['slot_memo'] ?? '')),
                'is_active' => (string) ($reservation['is_active'] ?? '1') === '1' ? 1 : 0,
            ];
        }

        return array_values($reservations);
    }

    private function slotDetailFormFromRequest(): array
    {
        return [
            'client_name' => trim((string) ($_POST['client_name'] ?? '')),
            'company_name' => trim((string) ($_POST['company_name'] ?? '')),
            'client_email' => trim((string) ($_POST['client_email'] ?? '')),
            'client_phone' => trim((string) ($_POST['client_phone'] ?? '')),
            'message' => trim((string) ($_POST['message'] ?? '')),
            'slot_memo' => trim((string) ($_POST['slot_memo'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function buildWeekQuery(string $week): string
    {
        return $week !== '' ? '?week=' . rawurlencode($week) : '';
    }
}
