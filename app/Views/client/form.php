<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Step 1</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink">お客様情報入力</h1>
        <?php if ($slot): ?>
            <p class="mt-3 text-sm text-slate-500">選択中: <?= e(format_datetime($slot['start_datetime'])) ?> - <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?> / <?= e((string) $slot['duration_minutes']) ?>分</p>
        <?php endif; ?>
    </div>

    <div class="rounded-[2rem] border border-line bg-white p-8 shadow-sm">
        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= e(implode(' ', $errors)) ?>
            </div>
        <?php endif; ?>

        <form action="/book/confirm" method="POST" class="grid gap-5 md:grid-cols-2">
            <?= csrf_field() ?>
            <input type="hidden" name="availability_slot_id" value="<?= e($form['availability_slot_id'] ?? '') ?>">

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">お名前</label>
                <input type="text" name="client_name" value="<?= e($form['client_name'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">会社名</label>
                <input type="text" name="company_name" value="<?= e($form['company_name'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">メールアドレス</label>
                <input type="email" name="client_email" value="<?= e($form['client_email'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">電話番号</label>
                <input type="text" name="client_phone" value="<?= e($form['client_phone'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">相談内容メモ</label>
                <textarea name="message" rows="5" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand"><?= e($form['message'] ?? '') ?></textarea>
            </div>
            <div class="md:col-span-2 flex items-center justify-between">
                <a href="/book" class="text-sm font-medium text-slate-500">日時選択へ戻る</a>
                <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">確認へ進む</button>
            </div>
        </form>
    </div>
</div>

