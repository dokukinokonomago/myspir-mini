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
                    日付を選んで、空いている開始時刻をタップしてください。`30分` または `60分` をその場で選べます。
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

            <div class="mb-6 lg:hidden">
                <div class="space-y-4">
                    <?php foreach ($calendar['days'] as $day): ?>
                        <?php $mobileSlots = $mobileAvailableByDay[$day['date']] ?? []; ?>
                        <section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm <?= $day['is_today'] ? 'ring-2 ring-blue-100' : '' ?>">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400"><?= e($weekdayMap[$day['weekday_short']] ?? $day['weekday_short']) ?></p>
                                    <h3 class="mt-2 text-lg font-semibold text-ink"><?= e($day['label']) ?></h3>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500"><?= $day['is_today'] ? 'Today' : 'Day' ?></span>
                            </div>

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
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="selection-modal" class="fixed inset-0 z-50 hidden bg-slate-950/35 px-4 py-6">
    <div class="mx-auto mt-auto max-w-2xl rounded-[2rem] bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-brand">Save Selection</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink">選択した空き枠を保存</h2>
                <p id="selection-summary" class="mt-3 text-sm leading-6 text-slate-500">日時を選択するとここに表示します。</p>
            </div>
            <button type="button" id="selection-modal-close" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600">閉じる</button>
        </div>

        <form action="/admin/availability-slots" method="POST" id="selection-form" class="grid gap-5 md:grid-cols-2">
            <?= csrf_field() ?>
            <input type="hidden" name="date" id="selection-date" value="<?= e($form['date'] ?? '') ?>">
            <input type="hidden" name="start_time" id="selection-start-time" value="<?= e($form['start_time'] ?? '') ?>">
            <input type="hidden" name="end_time" id="selection-end-time" value="<?= e($form['end_time'] ?? '') ?>">
            <input type="hidden" name="duration_minutes" id="selection-duration" value="<?= e($selectedDuration) ?>">

            <div class="md:col-span-2 rounded-3xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Selected Slot</p>
                <p id="selection-slot-pill" class="mt-2 text-lg font-semibold text-ink">未選択</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-3 block text-sm font-medium text-slate-700">作成方法</label>
                <div class="grid gap-3 md:grid-cols-3">
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="single" <?= checked(($form['recurrence_type'] ?? 'single') === 'single') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">1回だけ</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">この時間だけ保存します。</p>
                        </div>
                    </label>
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="weekly" <?= checked(($form['recurrence_type'] ?? 'single') === 'weekly') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">毎週</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">同じ曜日・同時刻で繰り返します。</p>
                        </div>
                    </label>
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="monthly" <?= checked(($form['recurrence_type'] ?? 'single') === 'monthly') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">毎月</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">同じ日付・同時刻で繰り返します。</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">作成回数</label>
                <input type="number" name="recurrence_count" id="selection-recurrence-count" min="1" max="24" value="<?= e($form['recurrence_count'] ?? '1') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
                <p class="mt-2 text-xs text-slate-500">毎週 / 毎月を選んだときだけ複数指定できます。</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">面談時間</label>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <span id="selection-duration-label" class="text-sm font-medium text-ink"><?= e($selectedDuration) ?>分</span>
                </div>
            </div>

            <label class="inline-flex items-center gap-3 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" <?= checked(($form['is_active'] ?? '1') === '1') ?> class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                すぐ公開する
            </label>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">メモ</label>
                <textarea name="memo" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand"><?= e($form['memo'] ?? '') ?></textarea>
            </div>

            <div class="md:col-span-2 flex flex-wrap justify-end gap-3">
                <button type="button" id="selection-clear" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">選択をクリア</button>
                <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">保存する</button>
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

    .calendar-cell.is-selected span {
        color: white;
        background: rgba(255, 255, 255, 0.12);
    }
</style>

<script>
    (() => {
        const modal = document.getElementById('selection-modal');
        const closeButton = document.getElementById('selection-modal-close');
        const clearButton = document.getElementById('selection-clear');
        const selectionSummary = document.getElementById('selection-summary');
        const selectionSlotPill = document.getElementById('selection-slot-pill');
        const selectionDateInput = document.getElementById('selection-date');
        const selectionStartInput = document.getElementById('selection-start-time');
        const selectionEndInput = document.getElementById('selection-end-time');
        const selectionDurationInput = document.getElementById('selection-duration');
        const selectionDurationLabel = document.getElementById('selection-duration-label');
        const recurrenceCountInput = document.getElementById('selection-recurrence-count');
        const recurrenceInputs = Array.from(document.querySelectorAll('input[name="recurrence_type"]'));
        const cells = Array.from(document.querySelectorAll('.calendar-cell'));
        const mobileSlotButtons = Array.from(document.querySelectorAll('.mobile-slot-button'));
        const hasServerErrors = <?= !empty($errors) ? 'true' : 'false' ?>;

        let dragState = null;
        let selection = null;

        cells.forEach((cell) => {
            cell.addEventListener('pointerdown', (event) => {
                if (cell.dataset.selectable !== 'true') {
                    return;
                }

                dragState = {
                    dayIndex: cell.dataset.dayIndex,
                    startIndex: Number(cell.dataset.slotIndex),
                };

                updateSelectionFromDrag(Number(cell.dataset.dayIndex), dragState.startIndex, dragState.startIndex);
                event.preventDefault();
            });

            cell.addEventListener('pointerenter', () => {
                if (!dragState || cell.dataset.selectable !== 'true') {
                    return;
                }

                if (cell.dataset.dayIndex !== dragState.dayIndex) {
                    return;
                }

                updateSelectionFromDrag(Number(cell.dataset.dayIndex), dragState.startIndex, Number(cell.dataset.slotIndex));
            });
        });

        document.addEventListener('pointerup', () => {
            if (!dragState || !selection) {
                dragState = null;
                return;
            }

            dragState = null;
            openModal();
        });

        closeButton.addEventListener('click', closeModal);
        clearButton.addEventListener('click', () => {
            clearSelection();
            closeModal();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        recurrenceInputs.forEach((input) => {
            input.addEventListener('change', syncRecurrenceControls);
        });

        mobileSlotButtons.forEach((button) => {
            button.addEventListener('click', () => {
                clearSelectionVisuals();
                selection = null;
                selectionDateInput.value = button.dataset.dayDate || '';
                selectionStartInput.value = button.dataset.startTime || '';
                selectionEndInput.value = button.dataset.endTime || '';
                selectionDurationInput.value = button.dataset.duration || '30';
                selectionDurationLabel.textContent = `${button.dataset.duration || '30'}分`;
                selectionSlotPill.textContent = `${button.dataset.dayDate} ${button.dataset.startTime} - ${button.dataset.endTime}`;
                selectionSummary.textContent = `${button.dataset.dayDate} の ${button.dataset.startTime} から ${button.dataset.endTime} までを新しい空き枠として保存します。`;
                openModal();
            });
        });

        syncRecurrenceControls();
        hydrateSelectionFromForm();

        function updateSelectionFromDrag(dayIndex, anchorIndex, hoveredIndex) {
            const offset = hoveredIndex - anchorIndex;
            const limitedOffset = Math.sign(offset) * Math.min(Math.abs(offset), 1);
            const normalizedStart = Math.min(anchorIndex, anchorIndex + limitedOffset);
            const normalizedEnd = Math.max(anchorIndex, anchorIndex + limitedOffset);

            selection = {
                dayIndex,
                startIndex: normalizedStart,
                endIndex: normalizedEnd,
            };

            paintSelection();
            syncModalFields();
        }

        function paintSelection() {
            cells.forEach((cell) => {
                const isSelected = selection
                    && Number(cell.dataset.dayIndex) === selection.dayIndex
                    && Number(cell.dataset.slotIndex) >= selection.startIndex
                    && Number(cell.dataset.slotIndex) <= selection.endIndex;

                cell.classList.toggle('is-selected', Boolean(isSelected));
            });
        }

        function syncModalFields() {
            if (!selection) {
                return;
            }

            const selectedCells = cells.filter((cell) =>
                Number(cell.dataset.dayIndex) === selection.dayIndex
                && Number(cell.dataset.slotIndex) >= selection.startIndex
                && Number(cell.dataset.slotIndex) <= selection.endIndex
            );

            if (selectedCells.length === 0) {
                return;
            }

            const firstCell = selectedCells[0];
            const lastCell = selectedCells[selectedCells.length - 1];
            const date = firstCell.dataset.dayDate;
            const startTime = firstCell.dataset.startTime;
            const endTime = slotIndexToTime(selection.endIndex + 1);
            const duration = selectedCells.length * <?= e((string) $slotStepMinutes) ?>;

            selectionDateInput.value = date;
            selectionStartInput.value = startTime;
            selectionEndInput.value = endTime;
            selectionDurationInput.value = String(duration);
            selectionDurationLabel.textContent = `${duration}分`;
            selectionSlotPill.textContent = `${date} ${startTime} - ${endTime}`;
            selectionSummary.textContent = `${date} の ${startTime} から ${endTime} までを新しい空き枠として保存します。`;
        }

        function slotIndexToTime(slotIndex) {
            const minutes = (<?= e((string) $timeStartHour) ?> * 60) + (slotIndex * <?= e((string) $slotStepMinutes) ?>);
            const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
            const minute = String(minutes % 60).padStart(2, '0');
            return `${hour}:${minute}`;
        }

        function openModal() {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function clearSelection() {
            selection = null;
            selectionDateInput.value = '';
            selectionStartInput.value = '';
            selectionEndInput.value = '';
            selectionDurationInput.value = '30';
            selectionDurationLabel.textContent = '30分';
            selectionSlotPill.textContent = '未選択';
            selectionSummary.textContent = '日時を選択するとここに表示します。';
            clearSelectionVisuals();
        }

        function clearSelectionVisuals() {
            cells.forEach((cell) => cell.classList.remove('is-selected'));
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

        function hydrateSelectionFromForm() {
            if (!selectionDateInput.value || !selectionStartInput.value || !selectionEndInput.value) {
                return;
            }

            const matchingStartCell = cells.find((cell) =>
                cell.dataset.dayDate === selectionDateInput.value
                && cell.dataset.startTime === selectionStartInput.value
            );

            if (!matchingStartCell) {
                return;
            }

            const startIndex = Number(matchingStartCell.dataset.slotIndex);
            const endIndex = timeToSlotIndex(selectionEndInput.value) - 1;
            selection = {
                dayIndex: Number(matchingStartCell.dataset.dayIndex),
                startIndex,
                endIndex: Math.max(startIndex, endIndex),
            };

            paintSelection();
            syncModalFields();

            if (hasServerErrors) {
                openModal();
            }
        }

        function timeToSlotIndex(time) {
            const [hour, minute] = time.split(':').map(Number);
            return (((hour * 60) + minute) - (<?= e((string) $timeStartHour) ?> * 60)) / <?= e((string) $slotStepMinutes) ?>;
        }
    })();
</script>
