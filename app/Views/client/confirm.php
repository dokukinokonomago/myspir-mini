<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Step 2</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink">予約内容の確認</h1>
        <p class="mt-3 text-sm text-slate-500">内容をご確認のうえ、予約を確定してください。</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">日時</h2>
            <p class="mt-4 text-2xl font-semibold tracking-tight text-ink"><?= e(format_datetime($slot['start_datetime'])) ?></p>
            <p class="mt-2 text-sm text-slate-500">終了 <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?> / <?= e((string) $slot['duration_minutes']) ?>分</p>
            <p class="mt-4 text-sm leading-6 text-slate-500"><?= e($slot['memo'] ?: 'オンライン面談') ?></p>
        </div>

        <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
            <h2 class="text-lg font-semibold text-ink">お客様情報</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">お名前</dt><dd class="text-slate-800"><?= e($form['client_name']) ?></dd></div>
                <div><dt class="text-slate-500">会社名</dt><dd class="text-slate-800"><?= e($form['company_name'] ?: '-') ?></dd></div>
                <div><dt class="text-slate-500">メールアドレス</dt><dd class="text-slate-800"><?= e($form['client_email']) ?></dd></div>
                <div><dt class="text-slate-500">電話番号</dt><dd class="text-slate-800"><?= e($form['client_phone']) ?></dd></div>
                <div><dt class="text-slate-500">相談内容</dt><dd class="whitespace-pre-wrap text-slate-800"><?= e($form['message'] ?: '-') ?></dd></div>
            </dl>
        </div>
    </div>

    <form action="/book/store" method="POST" class="flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-line bg-white p-6 shadow-sm">
        <?= csrf_field() ?>
        <?php foreach ($form as $key => $value): ?>
            <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
        <?php endforeach; ?>
        <button type="button" onclick="history.back()" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">修正する</button>
        <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">予約を確定する</button>
    </form>
</div>

