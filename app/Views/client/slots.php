<div class="mx-auto max-w-6xl space-y-8">
    <section class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Booking</p>
        <h1 class="mt-3 text-4xl font-semibold tracking-tight text-ink">ご都合のよい日時をお選びください</h1>
        <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-500">空いている時間枠のみ表示しています。日時を選択後、お客様情報をご入力ください。</p>
    </section>

    <?php if (!$slots): ?>
        <section class="rounded-[2rem] border border-line bg-white p-10 text-center shadow-sm">
            <h2 class="text-2xl font-semibold text-ink">現在ご案内できる空き枠がありません</h2>
            <p class="mt-3 text-sm text-slate-500">別日程の追加後に再度ご確認ください。</p>
        </section>
    <?php else: ?>
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($slots as $slot): ?>
                <a href="/book/slot/<?= e((string) $slot['id']) ?>" class="group rounded-[2rem] border border-line bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-brand hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-brand"><?= e(format_datetime($slot['start_datetime'], 'Y/m/d (D)')) ?></p>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-ink">
                                <?= e(format_datetime($slot['start_datetime'], 'H:i')) ?>
                                <span class="text-lg text-slate-400">- <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?></span>
                            </h2>
                        </div>
                        <span class="rounded-full bg-mist px-3 py-1 text-xs font-medium text-brand"><?= e((string) $slot['duration_minutes']) ?>分</span>
                    </div>
                    <p class="mt-5 text-sm leading-6 text-slate-500"><?= e($slot['memo'] ?: 'オンライン面談') ?></p>
                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-ink">
                        この日時を選ぶ
                        <span class="transition group-hover:translate-x-1">→</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>

