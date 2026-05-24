<?php
declare(strict_types=1);

$pageContainerClass = 'max-w-[1700px]';
$timeStartHour = (int) $calendar['time_start_hour'];
$timeEndHour = (int) $calendar['time_end_hour'];
$slotStepMinutes = (int) $calendar['slot_step_minutes'];
$slotCount = (int) ((($timeEndHour - $timeStartHour) * 60) / $slotStepMinutes);
$cellHeight = 34;
$weekdayMap = ['Mon' => '月', 'Tue' => '火', 'Wed' => '水', 'Thu' => '木', 'Fri' => '金', 'Sat' => '土', 'Sun' => '日'];
$now = new DateTimeImmutable();
$slotEventsJson = json_encode($calendar['slot_events'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$timeLabels = [];
for ($slotIndex = 0; $slotIndex < $slotCount; $slotIndex++) {
    $minutes = ($timeStartHour * 60) + ($slotIndex * $slotStepMinutes);
    $timeLabels[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

$occupiedCells = [];
foreach ($calendar['slot_events'] as $event) {
    $startMinutes = (((int) substr($event['start_time'], 0, 2)) * 60) + ((int) substr($event['start_time'], 3, 2));
    $endMinutes = (((int) substr($event['end_time'], 0, 2)) * 60) + ((int) substr($event['end_time'], 3, 2));
    $startRow = (int) floor(($startMinutes - ($timeStartHour * 60)) / $slotStepMinutes);
    $rowSpan = max(1, (int) (($endMinutes - $startMinutes) / $slotStepMinutes));

    for ($row = $startRow; $row < ($startRow + $rowSpan); $row++) {
        $occupiedCells[$event['day_index']][$row] = $event['status'];
    }
}

$selectedDuration = '30';
if (!empty($form['start_time']) && !empty($form['end_time'])) {
    $selectedDuration = (string) max(
        30,
        (((int) substr($form['end_time'], 0, 2) * 60) + ((int) substr($form['end_time'], 3, 2)))
        - (((int) substr($form['start_time'], 0, 2) * 60) + ((int) substr($form['start_time'], 3, 2)))
    );
}

$mobileAvailableByDay = [];
$mobileEventsByDay = [];
foreach ($calendar['days'] as $day) {
    $daySlots = [];

    for ($slotIndex = 0; $slotIndex < $slotCount; $slotIndex++) {
        $timeLabel = $timeLabels[$slotIndex];
        $slotDateTime = new DateTimeImmutable($day['date'] . ' ' . $timeLabel . ':00');
        $status = $occupiedCells[$day['index']][$slotIndex] ?? 'free';

        if ($slotDateTime < $now || $status !== 'free') {
            continue;
        }

        $canFitSixty = ($slotIndex + 1) < $slotCount
            && (($occupiedCells[$day['index']][$slotIndex + 1] ?? 'free') === 'free')
            && (new DateTimeImmutable($day['date'] . ' ' . $timeLabels[$slotIndex + 1] . ':00')) >= $now;

        $daySlots[] = [
            'slot_index' => $slotIndex,
            'start_time' => $timeLabel,
            'end_time_30' => $timeLabels[$slotIndex + 1] ?? sprintf('%02d:%02d', $timeEndHour, 0),
            'end_time_60' => $timeLabels[$slotIndex + 2] ?? sprintf('%02d:%02d', $timeEndHour, 0),
            'can_fit_sixty' => $canFitSixty && isset($timeLabels[$slotIndex + 2]),
        ];
    }

    $mobileAvailableByDay[$day['date']] = $daySlots;
    $mobileEventsByDay[$day['date']] = array_values(array_filter(
        $calendar['slot_events'],
        static fn (array $event): bool => $event['date'] === $day['date']
    ));
}
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-brand">Availability Planner</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">週カレンダーで空き枠を作成</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
                Spir 風に、カレンダー上の時間帯を直接なぞって空き枠を作成します。30分または60分の範囲をドラッグし、内容を確認して保存してください。
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="/admin/availability-slots?week=<?= e($calendar['week_start']) ?>" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">一覧を見る</a>
            <a href="/admin/availability-slots/create?week=<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">今週へ戻る</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= e(implode(' ', $errors)) ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-[2rem] border border-line bg-white p-4 shadow-sm md:p-5 xl:p-6">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">対象週</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">
                        <?= e($calendar['week_start_label']) ?> - <?= e($calendar['week_end_label']) ?>
                    </h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/admin/availability-slots/create?week=<?= e($calendar['prev_week']) ?>" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100">前の週</a>
                    <a href="/admin/availability-slots/create?week=<?= e($calendar['next_week']) ?>" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100">次の週</a>
                </div>
            </div>

            <div class="mb-5 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-4 lg:hidden">
                <p class="text-sm font-semibold text-ink">スマホ操作モード</p>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    作成モードでは日付ごとの開始時刻をタップし、削除モードでは既存枠カードから削除できます。
                </p>
            </div>

            <div class="mb-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-3xl bg-mist p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">公開中</p>
                    <p class="mt-2 text-2xl font-semibold text-ink"><?= e((string) $calendar['stats']['available']) ?></p>
                </div>
                <div class="rounded-3xl bg-rose-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-rose-300">予約済み</p>
                    <p class="mt-2 text-2xl font-semibold text-rose-700"><?= e((string) $calendar['stats']['booked']) ?></p>
                </div>
                <div class="rounded-3xl bg-slate-100 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">非表示</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-700"><?= e((string) $calendar['stats']['hidden']) ?></p>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2"><span class="h-2.5 w-2.5 rounded-full bg-brand"></span>公開中の空き枠</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>予約済み</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span>非表示</span>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2"><span class="h-2.5 w-2.5 rounded-full bg-ink"></span>新規選択中</span>
            </div>

            <div class="mb-5 flex flex-wrap gap-3">
                <button type="button" id="planner-mode-create" class="rounded-full bg-ink px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">作成モード</button>
                <button type="button" id="planner-mode-delete" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50">削除モード</button>
                <p id="planner-mode-copy" class="self-center text-sm text-slate-500">現在は作成モードです。空き時間を選択して、区切りを作って保存します。</p>
            </div>

            <div class="mb-6 lg:hidden">
                <div class="space-y-4">
                    <?php foreach ($calendar['days'] as $day): ?>
                        <?php $mobileSlots = $mobileAvailableByDay[$day['date']] ?? []; ?>
                        <?php $mobileEvents = $mobileEventsByDay[$day['date']] ?? []; ?>
                        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm <?= $day['is_today'] ? 'ring-2 ring-blue-100' : '' ?>">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400"><?= e($weekdayMap[$day['weekday_short']] ?? $day['weekday_short']) ?></p>
                                    <h3 class="mt-2 text-lg font-semibold text-ink"><?= e($day['label']) ?></h3>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"><?= $day['is_today'] ? 'Today' : 'Day' ?></span>
                            </div>

                            <div class="mobile-create-pane space-y-3">
                                <?php if (!$mobileSlots): ?>
                                    <p class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">この日は新規作成できる空き時間がありません。</p>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($mobileSlots as $slot): ?>
                                            <div class="rounded-3xl border border-slate-100 px-4 py-4">
                                                <div class="mb-3 flex items-center justify-between">
                                                    <p class="text-base font-semibold text-ink"><?= e($slot['start_time']) ?> 開始</p>
                                                    <span class="rounded-full bg-mist px-3 py-1 text-xs font-medium text-brand">Tap</span>
                                                </div>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <button
                                                        type="button"
                                                        class="mobile-slot-button rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm transition hover:border-brand hover:bg-mist"
                                                        data-day-date="<?= e($day['date']) ?>"
                                                        data-start-time="<?= e($slot['start_time']) ?>"
                                                        data-end-time="<?= e($slot['end_time_30']) ?>"
                                                        data-duration="30"
                                                    >
                                                        <span class="block font-semibold text-ink">30分で作成</span>
                                                        <span class="mt-1 block text-xs text-slate-500"><?= e($slot['start_time']) ?> - <?= e($slot['end_time_30']) ?></span>
                                                    </button>
                                                    <?php if ($slot['can_fit_sixty']): ?>
                                                        <button
                                                            type="button"
                                                            class="mobile-slot-button rounded-2xl border border-slate-200 px-4 py-3 text-left text-sm transition hover:border-brand hover:bg-mist"
                                                            data-day-date="<?= e($day['date']) ?>"
                                                            data-start-time="<?= e($slot['start_time']) ?>"
                                                            data-end-time="<?= e($slot['end_time_60']) ?>"
                                                            data-duration="60"
                                                        >
                                                            <span class="block font-semibold text-ink">60分で作成</span>
                                                            <span class="mt-1 block text-xs text-slate-500"><?= e($slot['start_time']) ?> - <?= e($slot['end_time_60']) ?></span>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mobile-delete-pane hidden space-y-3">
                                <?php if (!$mobileEvents): ?>
                                    <p class="rounded-2xl bg-slate-50 px-4 py-4 text-sm text-slate-500">この日の既存枠はありません。</p>
                                <?php else: ?>
                                    <?php foreach ($mobileEvents as $event): ?>
                                        <div class="rounded-3xl border border-slate-100 px-4 py-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-base font-semibold text-ink"><?= e($event['start_time']) ?> - <?= e($event['end_time']) ?></p>
                                                    <p class="mt-1 text-xs text-slate-500"><?= e($event['memo'] ?: ($event['status'] === 'booked' ? '予約あり' : '空き枠')) ?></p>
                                                </div>
                                                <span class="rounded-full px-3 py-1 text-[11px] font-medium <?= $event['status'] === 'booked' ? 'bg-rose-50 text-rose-700' : ($event['status'] === 'hidden' ? 'bg-slate-100 text-slate-600' : 'bg-mist text-brand') ?>">
                                                    <?= $event['status'] === 'booked' ? '予約済み' : ($event['status'] === 'hidden' ? '非表示' : '公開中') ?>
                                                </span>
                                            </div>
                                            <div class="mt-3 flex justify-end">
                                                <?php if ($event['can_delete']): ?>
                                                    <form action="/admin/availability-slots/<?= e((string) $event['id']) ?>/delete" method="POST" onsubmit="return confirm('この空き枠を削除しますか？');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="rounded-2xl border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50">この枠を削除</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="rounded-full bg-slate-100 px-4 py-2 text-[11px] font-medium text-slate-400">予約済みで削除不可</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="hidden overflow-auto rounded-[1.75rem] border border-slate-200 lg:block">
                <div class="min-w-[980px] bg-white">
                    <div class="grid border-b border-slate-200 bg-slate-50" style="grid-template-columns: 72px repeat(7, minmax(0, 1fr));">
                        <div class="border-r border-slate-200 px-3 py-4 text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Time</div>
                        <?php foreach ($calendar['days'] as $day): ?>
                            <div class="border-r border-slate-200 px-3 py-4 last:border-r-0 <?= $day['is_today'] ? 'bg-blue-50/80' : '' ?>">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400"><?= e($weekdayMap[$day['weekday_short']] ?? $day['weekday_short']) ?></p>
                                <p class="mt-2 text-lg font-semibold text-ink"><?= e($day['label']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid" style="grid-template-columns: 72px repeat(7, minmax(0, 1fr));">
                        <div class="border-r border-slate-200 bg-slate-50">
                            <?php foreach ($timeLabels as $slotIndex => $label): ?>
                                <div class="border-b border-slate-100 px-3 text-[11px] text-slate-400" style="height: <?= e((string) $cellHeight) ?>px; line-height: <?= e((string) $cellHeight) ?>px;">
                                    <?= $slotIndex % 2 === 0 ? e($label) : '' ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($calendar['days'] as $day): ?>
                            <div
                                class="calendar-day-column relative border-r border-slate-200 last:border-r-0"
                                data-day-index="<?= e((string) $day['index']) ?>"
                                data-day-date="<?= e($day['date']) ?>"
                                style="height: <?= e((string) ($slotCount * $cellHeight)) ?>px;"
                            >
                                <?php for ($slotIndex = 0; $slotIndex < $slotCount; $slotIndex++): ?>
                                    <?php
                                    $status = $occupiedCells[$day['index']][$slotIndex] ?? 'free';
                                    $timeLabel = $timeLabels[$slotIndex];
                                    $isPast = (new DateTimeImmutable($day['date'] . ' ' . $timeLabel . ':00')) < new DateTimeImmutable();
                                    $cellStatus = $isPast && $status === 'free' ? 'past' : $status;
                                    $isSelectable = $cellStatus === 'free';
                                    ?>
                                    <button
                                        type="button"
                                        class="calendar-cell absolute inset-x-0 border-b border-slate-100 px-2 text-left text-[10px] transition <?= $isSelectable ? 'hover:bg-blue-50' : '' ?>"
                                        data-day-index="<?= e((string) $day['index']) ?>"
                                        data-day-date="<?= e($day['date']) ?>"
                                        data-slot-index="<?= e((string) $slotIndex) ?>"
                                        data-start-time="<?= e($timeLabel) ?>"
                                        data-status="<?= e($cellStatus) ?>"
                                        data-selectable="<?= $isSelectable ? 'true' : 'false' ?>"
                                        data-delete-selectable="<?= $cellStatus !== 'past' ? 'true' : 'false' ?>"
                                        style="top: <?= e((string) ($slotIndex * $cellHeight)) ?>px; height: <?= e((string) $cellHeight) ?>px;"
                                    >
                                        <span class="pointer-events-none inline-block rounded-full px-2 py-1 text-[10px] text-transparent">
                                            <?= e($timeLabel) ?>
                                        </span>
                                    </button>
                                <?php endfor; ?>

                                <?php foreach ($calendar['slot_events'] as $event): ?>
                                    <?php if ((int) $event['day_index'] !== (int) $day['index']) {
                                        continue;
                                    }

                                    $eventStart = (((int) substr($event['start_time'], 0, 2)) * 60) + ((int) substr($event['start_time'], 3, 2));
                                    $eventEnd = (((int) substr($event['end_time'], 0, 2)) * 60) + ((int) substr($event['end_time'], 3, 2));
                                    $eventTop = (int) ((($eventStart - ($timeStartHour * 60)) / $slotStepMinutes) * $cellHeight);
                                    $eventHeight = max($cellHeight - 4, (int) ((($eventEnd - $eventStart) / $slotStepMinutes) * $cellHeight) - 4);
                                    $eventClass = match ($event['status']) {
                                        'booked' => 'bg-rose-500 text-white shadow-rose-100',
                                        'hidden' => 'bg-slate-300 text-slate-700 shadow-slate-100',
                                        default => 'bg-brand text-white shadow-blue-100',
                                    };
                                    ?>
                                    <div
                                        class="pointer-events-none absolute inset-x-1 z-10 rounded-2xl px-3 py-2 text-xs shadow-md <?= $eventClass ?>"
                                        style="top: <?= e((string) ($eventTop + 2)) ?>px; height: <?= e((string) $eventHeight) ?>px;"
                                    >
                                        <p class="font-semibold"><?= e($event['start_time']) ?> - <?= e($event['end_time']) ?></p>
                                        <p class="mt-1 truncate opacity-90">
                                            <?php if ($event['status'] === 'booked'): ?>
                                                予約済み: <?= e($event['client_name'] ?: '予約あり') ?>
                                            <?php else: ?>
                                                <?= e($event['memo'] ?: '空き枠') ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-4 2xl:sticky 2xl:top-6 2xl:self-start">
            <div class="rounded-[2rem] border border-line bg-white p-6 shadow-sm">
                <p class="text-sm uppercase tracking-[0.2em] text-brand">Device Modes</p>
                <h2 class="mt-2 text-xl font-semibold text-ink">PC / スマホ両対応</h2>
                <ol class="mt-4 space-y-3 text-sm leading-6 text-slate-500">
                    <li>1. PC は大きな週カレンダー上でドラッグ選択</li>
                    <li>2. スマホは日別カードから開始時刻をタップ選択</li>
                    <li>3. 保存モーダルは共通で、繰り返し設定も同じ導線です</li>
                </ol>
            </div>

            <div class="rounded-[2rem] border border-line bg-white p-6 shadow-sm">
                <p class="text-sm uppercase tracking-[0.2em] text-brand">This Week</p>
                <h2 class="mt-2 text-xl font-semibold text-ink">週の予定状況</h2>
                <div class="mt-4 space-y-3">
                    <?php if (!$calendar['slot_events']): ?>
                        <p class="text-sm text-slate-500">この週の空き枠はまだありません。</p>
                    <?php else: ?>
                        <?php foreach ($calendar['slot_events'] as $event): ?>
                            <div class="rounded-3xl border border-slate-100 px-4 py-4 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-ink"><?= e($event['date']) ?> <?= e($event['start_time']) ?> - <?= e($event['end_time']) ?></p>
                                        <p class="mt-1 text-xs text-slate-500"><?= e($event['memo'] ?: ($event['status'] === 'booked' ? '予約あり' : '空き枠')) ?></p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[11px] font-medium <?= $event['status'] === 'booked' ? 'bg-rose-50 text-rose-700' : ($event['status'] === 'hidden' ? 'bg-slate-100 text-slate-600' : 'bg-mist text-brand') ?>">
                                        <?= $event['status'] === 'booked' ? '予約済み' : ($event['status'] === 'hidden' ? '非表示' : '公開中') ?>
                                    </span>
                                </div>
                                <div class="mt-3 flex justify-end">
                                    <?php if ($event['can_delete']): ?>
                                        <form action="/admin/availability-slots/<?= e((string) $event['id']) ?>/delete" method="POST" onsubmit="return confirm('この空き枠を削除しますか？');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">この枠を削除</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="rounded-full bg-slate-100 px-4 py-2 text-[11px] font-medium text-slate-400">予約済みで削除不可</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="selection-modal" class="fixed inset-0 z-50 hidden bg-slate-950/35 px-4 py-6">
    <div class="mx-auto mt-auto max-w-4xl rounded-[2rem] bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-brand">Range Editor</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink">空き時間を分割して保存</h2>
                <p id="selection-summary" class="mt-3 text-sm leading-6 text-slate-500">まず大きな空き時間を選び、その後で自由に区切ります。</p>
            </div>
            <button type="button" id="selection-modal-close" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600">閉じる</button>
        </div>

        <form action="/admin/availability-slots" method="POST" id="selection-form" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <?= csrf_field() ?>
            <input type="hidden" name="date" id="selection-date" value="<?= e($form['date'] ?? '') ?>">
            <input type="hidden" name="start_time" id="selection-start-time" value="<?= e($form['start_time'] ?? '') ?>">
            <input type="hidden" name="end_time" id="selection-end-time" value="<?= e($form['end_time'] ?? '') ?>">
            <input type="hidden" name="duration_minutes" id="selection-duration" value="<?= e($selectedDuration) ?>">
            <input type="hidden" name="segments_json" id="selection-segments-json" value="<?= e($form['segments_json'] ?? '') ?>">

            <div class="space-y-5">
                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Step 1</p>
                    <p id="selection-slot-pill" class="mt-2 text-lg font-semibold text-ink">未選択</p>
                    <p class="mt-2 text-sm text-slate-500">ここで選択した大きな空き時間全体を確認します。</p>
                </div>

                <div class="rounded-3xl border border-slate-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Step 2</p>
                            <h3 class="mt-2 text-lg font-semibold text-ink">区切りを設定</h3>
                        </div>
                        <button type="button" id="reset-splits" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">区切りをリセット</button>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-500">下の境界ボタンを押すと、その位置で区切ります。もう一度押すと結合します。</p>
                    <div id="segment-strip" class="mt-4 rounded-3xl bg-slate-50 p-4"></div>
                    <div id="segment-divider-buttons" class="mt-3 flex flex-wrap gap-2"></div>
                </div>

                <div class="rounded-3xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Step 3</p>
                    <h3 class="mt-2 text-lg font-semibold text-ink">各枠の内容を入力</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">区切られた各枠ごとにメモと表示状態を個別設定できます。</p>
                    <div id="segment-cards" class="mt-4 space-y-3"></div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-3xl border border-slate-200 p-4">
                    <label class="mb-3 block text-sm font-medium text-slate-700">繰り返し設定</label>
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
                            <input type="radio" name="recurrence_type" value="single" <?= checked(($form['recurrence_type'] ?? 'single') === 'single') ?> class="mt-1">
                            <span><span class="block font-semibold text-ink">1回だけ</span><span class="mt-1 block text-xs text-slate-500">選択した日だけ保存します。</span></span>
                        </label>
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
                            <input type="radio" name="recurrence_type" value="weekly" <?= checked(($form['recurrence_type'] ?? 'single') === 'weekly') ?> class="mt-1">
                            <span><span class="block font-semibold text-ink">毎週</span><span class="mt-1 block text-xs text-slate-500">同じ曜日と区切りで繰り返します。</span></span>
                        </label>
                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 p-3">
                            <input type="radio" name="recurrence_type" value="monthly" <?= checked(($form['recurrence_type'] ?? 'single') === 'monthly') ?> class="mt-1">
                            <span><span class="block font-semibold text-ink">毎月</span><span class="mt-1 block text-xs text-slate-500">同じ日付と区切りで繰り返します。</span></span>
                        </label>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 p-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700">作成回数</label>
                    <input type="number" name="recurrence_count" id="selection-recurrence-count" min="1" max="24" value="<?= e($form['recurrence_count'] ?? '1') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
                    <p class="mt-2 text-xs text-slate-500">毎週 / 毎月を選んだときだけ複数指定できます。</p>
                </div>

                <div class="rounded-3xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total Segments</p>
                    <p id="segment-count-label" class="mt-2 text-2xl font-semibold text-ink">0</p>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" id="selection-clear" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">選択をクリア</button>
                    <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">保存する</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="delete-modal" class="fixed inset-0 z-50 hidden bg-slate-950/35 px-4 py-6">
    <div class="mx-auto mt-auto max-w-xl rounded-[2rem] bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-rose-500">Delete Range</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink">選択範囲の空き枠を削除</h2>
                <p id="delete-summary" class="mt-3 text-sm leading-6 text-slate-500">範囲を選択すると削除対象を表示します。</p>
            </div>
            <button type="button" id="delete-modal-close" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600">閉じる</button>
        </div>

        <form action="/admin/availability-slots/bulk-delete" method="POST" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="date" id="delete-date" value="">
            <input type="hidden" name="start_time" id="delete-start-time" value="">
            <input type="hidden" name="end_time" id="delete-end-time" value="">

            <div class="rounded-3xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Selected Range</p>
                <p id="delete-range-pill" class="mt-2 text-lg font-semibold text-ink">未選択</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-3xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-rose-300">削除対象</p>
                    <p id="delete-target-count" class="mt-2 text-2xl font-semibold text-rose-700">0</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">予約済みで残る件数</p>
                    <p id="delete-booked-count" class="mt-2 text-2xl font-semibold text-slate-700">0</p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 p-4">
                <p class="text-sm leading-6 text-slate-500">選択範囲の中で、未予約の空き枠だけを削除します。予約済み枠は保護されます。</p>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" id="delete-clear" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">選択をクリア</button>
                <button type="submit" id="delete-submit" class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-rose-700">選択範囲を削除</button>
            </div>
        </form>
    </div>
</div>

<style>
    .calendar-cell[data-status="past"] {
        background: linear-gradient(135deg, rgba(226, 232, 240, 0.65), rgba(248, 250, 252, 0.8));
    }

    .calendar-cell[data-status="available"],
    .calendar-cell[data-status="booked"],
    .calendar-cell[data-status="hidden"] {
        background-color: rgba(241, 245, 249, 0.8);
        cursor: not-allowed;
    }

    .calendar-cell.is-selected {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.88));
        border-color: rgba(15, 23, 42, 0.8);
        z-index: 20;
    }

    .calendar-cell.is-selected-delete {
        background: linear-gradient(180deg, rgba(225, 29, 72, 0.92), rgba(190, 24, 93, 0.9));
        border-color: rgba(190, 24, 93, 0.8);
        z-index: 20;
    }

    .calendar-cell.is-selected span,
    .calendar-cell.is-selected-delete span {
        color: white;
        background: rgba(255, 255, 255, 0.12);
    }

    .segment-strip-grid {
        display: grid;
        gap: 6px;
    }

    .segment-strip-cell {
        min-height: 68px;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(37, 99, 235, 0.16), rgba(37, 99, 235, 0.08));
        border: 1px solid rgba(37, 99, 235, 0.15);
        padding: 10px 8px;
        text-align: center;
        font-size: 11px;
        color: #1e3a8a;
    }

    .segment-strip-cell[data-break-after="true"] {
        box-shadow: inset -3px 0 0 rgba(15, 23, 42, 0.7);
    }

    .segment-divider-button[data-active="true"] {
        background: #0f172a;
        color: white;
        border-color: #0f172a;
    }
</style>

<script>
    (() => {
        const modal = document.getElementById('selection-modal');
        const closeButton = document.getElementById('selection-modal-close');
        const clearButton = document.getElementById('selection-clear');
        const deleteModal = document.getElementById('delete-modal');
        const deleteModalCloseButton = document.getElementById('delete-modal-close');
        const deleteClearButton = document.getElementById('delete-clear');
        const deleteSummary = document.getElementById('delete-summary');
        const deleteRangePill = document.getElementById('delete-range-pill');
        const deleteDateInput = document.getElementById('delete-date');
        const deleteStartInput = document.getElementById('delete-start-time');
        const deleteEndInput = document.getElementById('delete-end-time');
        const deleteTargetCount = document.getElementById('delete-target-count');
        const deleteBookedCount = document.getElementById('delete-booked-count');
        const deleteSubmitButton = document.getElementById('delete-submit');
        const resetSplitsButton = document.getElementById('reset-splits');
        const selectionSummary = document.getElementById('selection-summary');
        const selectionSlotPill = document.getElementById('selection-slot-pill');
        const selectionDateInput = document.getElementById('selection-date');
        const selectionStartInput = document.getElementById('selection-start-time');
        const selectionEndInput = document.getElementById('selection-end-time');
        const selectionDurationInput = document.getElementById('selection-duration');
        const selectionSegmentsJsonInput = document.getElementById('selection-segments-json');
        const recurrenceCountInput = document.getElementById('selection-recurrence-count');
        const recurrenceInputs = Array.from(document.querySelectorAll('input[name="recurrence_type"]'));
        const cells = Array.from(document.querySelectorAll('.calendar-cell'));
        const mobileSlotButtons = Array.from(document.querySelectorAll('.mobile-slot-button'));
        const mobileCreatePanes = Array.from(document.querySelectorAll('.mobile-create-pane'));
        const mobileDeletePanes = Array.from(document.querySelectorAll('.mobile-delete-pane'));
        const segmentStrip = document.getElementById('segment-strip');
        const dividerButtons = document.getElementById('segment-divider-buttons');
        const segmentCards = document.getElementById('segment-cards');
        const segmentCountLabel = document.getElementById('segment-count-label');
        const plannerModeCreateButton = document.getElementById('planner-mode-create');
        const plannerModeDeleteButton = document.getElementById('planner-mode-delete');
        const plannerModeCopy = document.getElementById('planner-mode-copy');
        const hasServerErrors = <?= !empty($errors) ? 'true' : 'false' ?>;
        const slotEvents = <?= $slotEventsJson ?: '[]' ?>;

        let dragState = null;
        let selection = null;
        let splitPoints = new Set();
        let segmentMeta = [];
        let plannerMode = 'create';

        cells.forEach((cell) => {
            cell.addEventListener('pointerdown', (event) => {
                if (!canStartSelection(cell)) {
                    return;
                }

                dragState = {
                    mode: plannerMode,
                    dayIndex: Number(cell.dataset.dayIndex),
                    startIndex: Number(cell.dataset.slotIndex),
                };

                updateSelectionFromDrag(dragState.dayIndex, dragState.startIndex, dragState.startIndex);
                event.preventDefault();
            });

            cell.addEventListener('pointerenter', () => {
                if (!dragState || !canExtendSelection(cell, dragState.mode)) {
                    return;
                }

                if (Number(cell.dataset.dayIndex) !== dragState.dayIndex) {
                    return;
                }

                updateSelectionFromDrag(dragState.dayIndex, dragState.startIndex, Number(cell.dataset.slotIndex));
            });
        });

        document.addEventListener('pointerup', () => {
            if (!dragState || !selection) {
                dragState = null;
                return;
            }

            const finishedMode = dragState.mode;
            dragState = null;

            if (finishedMode === 'delete') {
                syncDeleteModalFields();
                openDeleteModal();
                return;
            }

            resetSplits();
            syncModalFields();
            openModal();
        });

        closeButton.addEventListener('click', closeModal);
        clearButton.addEventListener('click', () => {
            clearSelection();
            closeModal();
        });
        deleteModalCloseButton.addEventListener('click', closeDeleteModal);
        deleteClearButton.addEventListener('click', () => {
            clearSelection();
            closeDeleteModal();
        });

        resetSplitsButton.addEventListener('click', () => {
            resetSplits();
            syncModalFields();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        recurrenceInputs.forEach((input) => {
            input.addEventListener('change', syncRecurrenceControls);
        });

        plannerModeCreateButton.addEventListener('click', () => {
            setPlannerMode('create');
        });

        plannerModeDeleteButton.addEventListener('click', () => {
            setPlannerMode('delete');
        });

        mobileSlotButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setPlannerMode('create');
                clearSelectionVisuals();
                selection = {
                    dayIndex: findDayIndex(button.dataset.dayDate || ''),
                    startIndex: timeToSlotIndex(button.dataset.startTime || '00:00'),
                    endIndex: timeToSlotIndex(button.dataset.endTime || '00:30') - 1,
                    date: button.dataset.dayDate || '',
                };
                resetSplits();
                syncModalFields();
                openModal();
            });
        });

        setPlannerMode('create', true);
        syncRecurrenceControls();
        hydrateSelectionFromForm();

        function canStartSelection(cell) {
            return plannerMode === 'delete'
                ? cell.dataset.deleteSelectable === 'true'
                : cell.dataset.selectable === 'true';
        }

        function canExtendSelection(cell, mode) {
            return mode === 'delete'
                ? cell.dataset.deleteSelectable === 'true'
                : cell.dataset.selectable === 'true';
        }

        function updateSelectionFromDrag(dayIndex, anchorIndex, hoveredIndex) {
            const activeMode = dragState?.mode || plannerMode;
            const reachableEnd = getReachableEndIndex(dayIndex, anchorIndex, hoveredIndex, activeMode);
            const normalizedStart = Math.min(anchorIndex, reachableEnd);
            const normalizedEnd = Math.max(anchorIndex, reachableEnd);
            const dayDate = cells.find((cell) =>
                Number(cell.dataset.dayIndex) === dayIndex && Number(cell.dataset.slotIndex) === normalizedStart
            )?.dataset.dayDate || '';

            selection = {
                dayIndex,
                startIndex: normalizedStart,
                endIndex: normalizedEnd,
                date: dayDate,
            };

            paintSelection();
        }

        function getReachableEndIndex(dayIndex, startIndex, hoveredIndex, mode) {
            const direction = hoveredIndex >= startIndex ? 1 : -1;
            let current = startIndex;

            while (current !== hoveredIndex) {
                const next = current + direction;
                const nextCell = getCell(dayIndex, next);

                if (!nextCell || !canExtendSelection(nextCell, mode)) {
                    break;
                }

                current = next;
            }

            return current;
        }

        function paintSelection() {
            cells.forEach((cell) => {
                const isSelected = selection
                    && Number(cell.dataset.dayIndex) === selection.dayIndex
                    && Number(cell.dataset.slotIndex) >= selection.startIndex
                    && Number(cell.dataset.slotIndex) <= selection.endIndex;

                cell.classList.toggle('is-selected', Boolean(isSelected) && plannerMode === 'create');
                cell.classList.toggle('is-selected-delete', Boolean(isSelected) && plannerMode === 'delete');
            });
        }

        function resetSplits() {
            splitPoints = new Set();
            segmentMeta = [];
        }

        function syncModalFields() {
            if (!selection) {
                return;
            }

            const date = selection.date;
            const startTime = slotIndexToTime(selection.startIndex);
            const endTime = slotIndexToTime(selection.endIndex + 1);
            const duration = (selection.endIndex - selection.startIndex + 1) * <?= e((string) $slotStepMinutes) ?>;

            selectionDateInput.value = date;
            selectionStartInput.value = startTime;
            selectionEndInput.value = endTime;
            selectionDurationInput.value = String(duration);
            selectionSlotPill.textContent = `${date} ${startTime} - ${endTime}`;
            selectionSummary.textContent = `${date} の大きな空き時間を選択しました。次に区切り位置を決めて、各枠の内容を調整してください。`;

            renderSegmentEditor();
        }

        function syncDeleteModalFields() {
            if (!selection) {
                return;
            }

            const date = selection.date;
            const startTime = slotIndexToTime(selection.startIndex);
            const endTime = slotIndexToTime(selection.endIndex + 1);
            const affectedSlots = getAffectedSlots(date, startTime, endTime);
            const deletableCount = affectedSlots.filter((slot) => slot.can_delete).length;
            const bookedCount = affectedSlots.length - deletableCount;

            deleteDateInput.value = date;
            deleteStartInput.value = startTime;
            deleteEndInput.value = endTime;
            deleteRangePill.textContent = `${date} ${startTime} - ${endTime}`;
            deleteTargetCount.textContent = String(deletableCount);
            deleteBookedCount.textContent = String(bookedCount);

            if (deletableCount > 0) {
                deleteSummary.textContent = `${date} の選択範囲にある空き枠をまとめて削除します。予約済み枠は保護されます。`;
                deleteSubmitButton.disabled = false;
                deleteSubmitButton.classList.remove('cursor-not-allowed', 'bg-slate-300', 'hover:bg-slate-300');
                deleteSubmitButton.classList.add('bg-rose-600', 'hover:bg-rose-700');
                return;
            }

            if (bookedCount > 0) {
                deleteSummary.textContent = 'この範囲には予約済み枠のみが含まれています。削除は実行できません。';
            } else {
                deleteSummary.textContent = 'この範囲には削除できる既存の空き枠がありません。';
            }

            deleteSubmitButton.disabled = true;
            deleteSubmitButton.classList.add('cursor-not-allowed', 'bg-slate-300', 'hover:bg-slate-300');
            deleteSubmitButton.classList.remove('bg-rose-600', 'hover:bg-rose-700');
        }

        function renderSegmentEditor() {
            if (!selection) {
                return;
            }

            const totalSlots = selection.endIndex - selection.startIndex + 1;
            const segments = buildSegments();
            segmentCountLabel.textContent = String(segments.length);

            segmentStrip.innerHTML = '';
            const stripGrid = document.createElement('div');
            stripGrid.className = 'segment-strip-grid';
            stripGrid.style.gridTemplateColumns = `repeat(${totalSlots}, minmax(0, 1fr))`;

            for (let offset = 0; offset < totalSlots; offset++) {
                const absoluteIndex = selection.startIndex + offset;
                const cell = document.createElement('div');
                cell.className = 'segment-strip-cell';
                cell.dataset.breakAfter = splitPoints.has(offset + 1) ? 'true' : 'false';
                cell.innerHTML = `<span class="block font-semibold">${slotIndexToTime(absoluteIndex)}</span><span class="mt-1 block text-[10px]">${slotIndexToTime(absoluteIndex + 1)}</span>`;
                stripGrid.appendChild(cell);
            }

            segmentStrip.appendChild(stripGrid);
            renderDividerButtons(totalSlots);
            renderSegmentCards(segments);
            syncSegmentsJson(segments);
        }

        function renderDividerButtons(totalSlots) {
            dividerButtons.innerHTML = '';

            if (totalSlots <= 1) {
                const message = document.createElement('p');
                message.className = 'text-xs text-slate-500';
                message.textContent = '30分枠なので、これ以上の分割はありません。';
                dividerButtons.appendChild(message);
                return;
            }

            for (let position = 1; position < totalSlots; position++) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'segment-divider-button rounded-full border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100';
                button.dataset.active = splitPoints.has(position) ? 'true' : 'false';
                button.textContent = `${slotIndexToTime(selection.startIndex + position)} で区切る`;
                button.addEventListener('click', () => {
                    if (splitPoints.has(position)) {
                        splitPoints.delete(position);
                    } else {
                        splitPoints.add(position);
                    }
                    renderSegmentEditor();
                });
                dividerButtons.appendChild(button);
            }
        }

        function buildSegments() {
            if (!selection) {
                return [];
            }

            const totalSlots = selection.endIndex - selection.startIndex + 1;
            const sortedBreaks = Array.from(splitPoints).sort((left, right) => left - right);
            const boundaries = [0, ...sortedBreaks, totalSlots];
            const nextMeta = [];

            for (let index = 0; index < boundaries.length - 1; index++) {
                const startOffset = boundaries[index];
                const endOffset = boundaries[index + 1];
                const startIndex = selection.startIndex + startOffset;
                const endIndexExclusive = selection.startIndex + endOffset;
                const defaultMeta = segmentMeta[index] ?? { memo: '', is_active: true };

                nextMeta.push({
                    memo: defaultMeta.memo ?? '',
                    is_active: defaultMeta.is_active !== false,
                });
            }

            segmentMeta = nextMeta;

            return boundaries.slice(0, -1).map((startOffset, index) => {
                const endOffset = boundaries[index + 1];
                const startIndex = selection.startIndex + startOffset;
                const endIndexExclusive = selection.startIndex + endOffset;
                const duration = (endOffset - startOffset) * <?= e((string) $slotStepMinutes) ?>;

                return {
                    index,
                    date: selection.date,
                    start_time: slotIndexToTime(startIndex),
                    end_time: slotIndexToTime(endIndexExclusive),
                    duration_minutes: duration,
                    memo: segmentMeta[index]?.memo ?? '',
                    is_active: segmentMeta[index]?.is_active !== false,
                };
            });
        }

        function renderSegmentCards(segments) {
            segmentCards.innerHTML = '';

            if (segments.length === 0) {
                return;
            }

            segments.forEach((segment) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-3xl border border-slate-100 p-4';
                wrapper.innerHTML = `
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-ink">枠 ${segment.index + 1}: ${segment.start_time} - ${segment.end_time}</p>
                            <p class="mt-1 text-xs text-slate-500">${segment.duration_minutes}分</p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" data-segment-active="${segment.index}" ${segment.is_active ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 text-brand">
                            公開する
                        </label>
                    </div>
                    <div class="mt-3">
                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Memo</label>
                        <textarea data-segment-memo="${segment.index}" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand">${escapeHtml(segment.memo)}</textarea>
                    </div>
                `;
                segmentCards.appendChild(wrapper);
            });

            segmentCards.querySelectorAll('[data-segment-memo]').forEach((textarea) => {
                textarea.addEventListener('input', (event) => {
                    const index = Number(event.target.dataset.segmentMemo);
                    segmentMeta[index] = {
                        ...(segmentMeta[index] ?? { is_active: true, memo: '' }),
                        memo: event.target.value,
                    };
                    syncSegmentsJson(buildSegments());
                });
            });

            segmentCards.querySelectorAll('[data-segment-active]').forEach((checkbox) => {
                checkbox.addEventListener('change', (event) => {
                    const index = Number(event.target.dataset.segmentActive);
                    segmentMeta[index] = {
                        ...(segmentMeta[index] ?? { is_active: true, memo: '' }),
                        is_active: event.target.checked,
                    };
                    syncSegmentsJson(buildSegments());
                });
            });
        }

        function syncSegmentsJson(segments) {
            selectionSegmentsJsonInput.value = JSON.stringify(segments.map((segment) => ({
                date: segment.date,
                start_time: segment.start_time,
                end_time: segment.end_time,
                memo: segment.memo,
                is_active: segment.is_active ? '1' : '0',
            })));
        }

        function slotIndexToTime(slotIndex) {
            const minutes = (<?= e((string) $timeStartHour) ?> * 60) + (slotIndex * <?= e((string) $slotStepMinutes) ?>);
            const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
            const minute = String(minutes % 60).padStart(2, '0');
            return `${hour}:${minute}`;
        }

        function timeToSlotIndex(time) {
            const [hour, minute] = time.split(':').map(Number);
            return (((hour * 60) + minute) - (<?= e((string) $timeStartHour) ?> * 60)) / <?= e((string) $slotStepMinutes) ?>;
        }

        function findDayIndex(date) {
            const cell = cells.find((item) => item.dataset.dayDate === date);
            return cell ? Number(cell.dataset.dayIndex) : 0;
        }

        function getCell(dayIndex, slotIndex) {
            return cells.find((cell) =>
                Number(cell.dataset.dayIndex) === dayIndex && Number(cell.dataset.slotIndex) === slotIndex
            ) || null;
        }

        function openModal() {
            closeDeleteModal(false);
            modal.classList.remove('hidden');
            syncBodyScrollLock();
        }

        function closeModal() {
            modal.classList.add('hidden');
            syncBodyScrollLock();
        }

        function openDeleteModal() {
            closeModal();
            deleteModal.classList.remove('hidden');
            syncBodyScrollLock();
        }

        function closeDeleteModal(syncScrollLock = true) {
            deleteModal.classList.add('hidden');
            if (syncScrollLock) {
                syncBodyScrollLock();
            }
        }

        function syncBodyScrollLock() {
            const hasOpenModal = !modal.classList.contains('hidden') || !deleteModal.classList.contains('hidden');
            document.body.style.overflow = hasOpenModal ? 'hidden' : '';
        }

        function clearSelection() {
            selection = null;
            splitPoints = new Set();
            segmentMeta = [];
            selectionDateInput.value = '';
            selectionStartInput.value = '';
            selectionEndInput.value = '';
            selectionDurationInput.value = '30';
            selectionSegmentsJsonInput.value = '';
            selectionSlotPill.textContent = '未選択';
            selectionSummary.textContent = 'まず大きな空き時間を選び、その後で自由に区切ります。';
            deleteDateInput.value = '';
            deleteStartInput.value = '';
            deleteEndInput.value = '';
            deleteRangePill.textContent = '未選択';
            deleteSummary.textContent = '範囲を選択すると削除対象を表示します。';
            deleteTargetCount.textContent = '0';
            deleteBookedCount.textContent = '0';
            deleteSubmitButton.disabled = false;
            deleteSubmitButton.classList.remove('cursor-not-allowed', 'bg-slate-300', 'hover:bg-slate-300');
            deleteSubmitButton.classList.add('bg-rose-600', 'hover:bg-rose-700');
            segmentStrip.innerHTML = '';
            dividerButtons.innerHTML = '';
            segmentCards.innerHTML = '';
            segmentCountLabel.textContent = '0';
            clearSelectionVisuals();
        }

        function clearSelectionVisuals() {
            cells.forEach((cell) => {
                cell.classList.remove('is-selected');
                cell.classList.remove('is-selected-delete');
            });
        }

        function syncRecurrenceControls() {
            const recurrenceType = document.querySelector('input[name="recurrence_type"]:checked')?.value || 'single';

            if (recurrenceType === 'single') {
                recurrenceCountInput.value = '1';
                recurrenceCountInput.setAttribute('readonly', 'readonly');
                recurrenceCountInput.classList.add('bg-slate-100', 'text-slate-400');
                return;
            }

            recurrenceCountInput.removeAttribute('readonly');
            recurrenceCountInput.classList.remove('bg-slate-100', 'text-slate-400');
        }

        function setPlannerMode(mode, preserveFormState = false) {
            plannerMode = mode;
            if (preserveFormState) {
                clearSelectionVisuals();
            } else {
                clearSelection();
            }
            closeModal();
            closeDeleteModal();

            const isCreateMode = mode === 'create';
            plannerModeCreateButton.className = isCreateMode
                ? 'rounded-full bg-ink px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800'
                : 'rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100';
            plannerModeDeleteButton.className = isCreateMode
                ? 'rounded-full border border-rose-200 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50'
                : 'rounded-full bg-rose-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-700';
            plannerModeCopy.textContent = isCreateMode
                ? '現在は作成モードです。空き時間を選択して、区切りを作って保存します。'
                : '現在は削除モードです。削除したい範囲を選択すると、対象件数を確認して一括削除できます。';
            mobileCreatePanes.forEach((pane) => {
                pane.classList.toggle('hidden', !isCreateMode);
            });
            mobileDeletePanes.forEach((pane) => {
                pane.classList.toggle('hidden', isCreateMode);
            });
            paintSelection();
        }

        function hydrateSelectionFromForm() {
            if (!selectionDateInput.value || !selectionStartInput.value || !selectionEndInput.value) {
                return;
            }

            const dayIndex = findDayIndex(selectionDateInput.value);
            const startIndex = timeToSlotIndex(selectionStartInput.value);
            const endIndex = timeToSlotIndex(selectionEndInput.value) - 1;

            selection = {
                dayIndex,
                startIndex,
                endIndex,
                date: selectionDateInput.value,
            };

            paintSelection();

            if (selectionSegmentsJsonInput.value) {
                try {
                    const decoded = JSON.parse(selectionSegmentsJsonInput.value);
                    const boundaries = [];
                    segmentMeta = [];

                    decoded.forEach((segment, index) => {
                        segmentMeta.push({
                            memo: String(segment.memo ?? ''),
                            is_active: String(segment.is_active ?? '1') === '1',
                        });

                        if (index === 0) {
                            return;
                        }

                        const splitOffset = timeToSlotIndex(segment.start_time) - startIndex;
                        if (splitOffset > 0) {
                            boundaries.push(splitOffset);
                        }
                    });

                    splitPoints = new Set(boundaries);
                } catch (_error) {
                    resetSplits();
                }
            } else {
                resetSplits();
            }

            syncModalFields();

            if (hasServerErrors) {
                openModal();
            }
        }

        function getAffectedSlots(date, startTime, endTime) {
            return slotEvents.filter((slot) => (
                slot.date === date
                && slot.start_time >= startTime
                && slot.end_time <= endTime
            ));
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    })();
</script>
