<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-brand">Availability</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">空き枠一覧</h1>
        </div>
        <a href="/admin/availability-slots/create" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">空き枠を追加</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-medium">日時</th>
                        <th class="px-5 py-4 font-medium">面談時間</th>
                        <th class="px-5 py-4 font-medium">状態</th>
                        <th class="px-5 py-4 font-medium">予約</th>
                        <th class="px-5 py-4 font-medium">メモ</th>
                        <th class="px-5 py-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($slots as $slot): ?>
                        <tr class="align-top">
                            <td class="px-5 py-4 text-slate-700">
                                <?= e(format_datetime($slot['start_datetime'])) ?><br>
                                <span class="text-slate-400">- <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?></span>
                            </td>
                            <td class="px-5 py-4 text-slate-700"><?= e((string) $slot['duration_minutes']) ?>分</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-medium <?= (int) $slot['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= (int) $slot['is_active'] === 1 ? '表示中' : '非表示' ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <?php if ($slot['booking_id']): ?>
                                    <a href="/admin/bookings/<?= e((string) $slot['booking_id']) ?>" class="font-medium text-brand"><?= e($slot['client_name']) ?> 様</a>
                                    <div class="text-xs text-slate-500"><?= e($slot['client_email']) ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400">未予約</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-600"><?= e($slot['memo'] ?: '-') ?></td>
                            <td class="px-5 py-4 text-right">
                                <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/toggle" method="POST">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                        <?= (int) $slot['is_active'] === 1 ? '非表示にする' : '表示する' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$slots): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-500">空き枠はまだありません。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

