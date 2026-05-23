<div class="space-y-6">
    <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Dashboard</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink"><?= e($user['name'] ?? 'Admin') ?> さんの管理画面</h1>
        <p class="mt-3 text-sm text-slate-500">Google 連携 <?= $googleConnected ? '接続済み' : '未接続' ?> / 直近予約と空き枠をここから管理します。</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">総空き枠数</p>
            <p class="mt-3 text-3xl font-semibold text-ink"><?= e((string) $stats['slots_total']) ?></p>
        </div>
        <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">公開中空き枠</p>
            <p class="mt-3 text-3xl font-semibold text-ink"><?= e((string) $stats['slots_active']) ?></p>
        </div>
        <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-500">確定予約数</p>
            <p class="mt-3 text-3xl font-semibold text-ink"><?= e((string) $stats['bookings_total']) ?></p>
        </div>
    </div>

    <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-ink">直近の予約</h2>
            <a href="/admin/bookings" class="text-sm font-medium text-brand">予約一覧へ</a>
        </div>
        <?php if (!$upcomingBookings): ?>
            <p class="text-sm text-slate-500">まだ予約はありません。</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($upcomingBookings as $booking): ?>
                    <a href="/admin/bookings/<?= e((string) $booking['id']) ?>" class="block rounded-2xl border border-slate-100 px-4 py-4 transition hover:border-brand hover:bg-mist">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-ink"><?= e($booking['client_name']) ?> 様</p>
                                <p class="text-sm text-slate-500"><?= e($booking['client_email']) ?></p>
                            </div>
                            <div class="text-sm text-slate-600">
                                <?= e(format_datetime($booking['booked_start_datetime'])) ?> - <?= e(format_datetime($booking['booked_end_datetime'], 'H:i')) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

