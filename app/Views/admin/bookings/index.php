<div class="space-y-6">
    <div>
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Bookings</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">予約一覧</h1>
    </div>

    <div class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-medium">予約日時</th>
                        <th class="px-5 py-4 font-medium">顧客情報</th>
                        <th class="px-5 py-4 font-medium">面談時間</th>
                        <th class="px-5 py-4 font-medium">Meet</th>
                        <th class="px-5 py-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td class="px-5 py-4 text-slate-700">
                                <?= e(format_datetime($booking['booked_start_datetime'])) ?><br>
                                <span class="text-slate-400">- <?= e(format_datetime($booking['booked_end_datetime'], 'H:i')) ?></span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-ink"><?= e($booking['client_name']) ?> 様</div>
                                <div class="text-xs text-slate-500"><?= e($booking['company_name'] ?: '-') ?></div>
                                <div class="text-xs text-slate-500"><?= e($booking['client_email']) ?></div>
                            </td>
                            <td class="px-5 py-4 text-slate-700"><?= e((string) $booking['duration_minutes']) ?>分</td>
                            <td class="px-5 py-4">
                                <?php if ($booking['google_meet_url']): ?>
                                    <a href="<?= e($booking['google_meet_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-brand underline decoration-slate-200 underline-offset-4">Meetを開く</a>
                                <?php else: ?>
                                    <span class="text-slate-400">未発行</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="/admin/bookings/<?= e((string) $booking['id']) ?>" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">詳細</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$bookings): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">予約はまだありません。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

