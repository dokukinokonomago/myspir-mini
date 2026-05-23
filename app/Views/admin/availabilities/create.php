<div class="space-y-6">
    <div>
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Availability</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">空き枠追加</h1>
    </div>

    <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= e(implode(' ', $errors)) ?>
            </div>
        <?php endif; ?>

        <form action="/admin/availability-slots" method="POST" class="grid gap-5 md:grid-cols-2">
            <?= csrf_field() ?>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">日付</label>
                <input type="date" name="date" value="<?= e($form['date'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">面談時間</label>
                <select name="duration_minutes" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand">
                    <option value="30" <?= selected($form['duration_minutes'] ?? '30', '30') ?>>30分</option>
                    <option value="60" <?= selected($form['duration_minutes'] ?? '30', '60') ?>>60分</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">開始時間</label>
                <input type="time" name="start_time" value="<?= e($form['start_time'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">終了時間</label>
                <input type="time" name="end_time" value="<?= e($form['end_time'] ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">メモ</label>
                <textarea name="memo" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand"><?= e($form['memo'] ?? '') ?></textarea>
            </div>
            <label class="inline-flex items-center gap-3 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" <?= checked(($form['is_active'] ?? '1') === '1') ?> class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                すぐ公開する
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">保存する</button>
            </div>
        </form>
    </div>
</div>

