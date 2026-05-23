<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-[0.2em] text-accent">Complete</p>
        <h1 class="mt-3 text-4xl font-semibold tracking-tight text-ink">予約が完了しました</h1>
        <p class="mt-4 text-sm leading-7 text-slate-500">以下の Google Meet リンクから当日ご参加ください。</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">予約日時</h2>
            <p class="mt-4 text-2xl font-semibold tracking-tight text-ink"><?= e(format_datetime($booking['booked_start_datetime'])) ?></p>
            <p class="mt-2 text-sm text-slate-500">終了 <?= e(format_datetime($booking['booked_end_datetime'], 'H:i')) ?> / <?= e((string) $booking['duration_minutes']) ?>分</p>
        </div>

        <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">Google Meet</h2>
            <a href="<?= e($booking['google_meet_url']) ?>" target="_blank" rel="noopener noreferrer" class="mt-4 block break-all text-sm font-medium text-brand underline decoration-slate-200 underline-offset-4">
                <?= e($booking['google_meet_url']) ?>
            </a>
        </div>
    </div>
</div>
