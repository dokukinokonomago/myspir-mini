<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-brand">Booking Detail</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">予約詳細</h1>
        </div>
        <a href="/admin/bookings" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100">一覧へ戻る</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">基本情報</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">名前</dt>
                    <dd class="mt-1 text-slate-800"><?= e($booking['client_name']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">会社名</dt>
                    <dd class="mt-1 text-slate-800"><?= e($booking['company_name'] ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">メールアドレス</dt>
                    <dd class="mt-1 text-slate-800"><?= e($booking['client_email']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">電話番号</dt>
                    <dd class="mt-1 text-slate-800"><?= e($booking['client_phone']) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">相談内容</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-slate-800"><?= e($booking['message'] ?: '-') ?></dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">予約内容</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500">予約日時</dt>
                    <dd class="mt-1 text-slate-800"><?= e(format_datetime($booking['booked_start_datetime'])) ?> - <?= e(format_datetime($booking['booked_end_datetime'], 'H:i')) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">面談時間</dt>
                    <dd class="mt-1 text-slate-800"><?= e((string) $booking['duration_minutes']) ?>分</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Google Event ID</dt>
                    <dd class="mt-1 break-all text-slate-800"><?= e($booking['google_event_id'] ?: '-') ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Google Meet</dt>
                    <dd class="mt-1 text-slate-800">
                        <?php if ($booking['google_meet_url']): ?>
                            <a href="<?= e($booking['google_meet_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-brand underline decoration-slate-200 underline-offset-4"><?= e($booking['google_meet_url']) ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">メモ</dt>
                    <dd class="mt-1 text-slate-800"><?= e($booking['memo'] ?: '-') ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

