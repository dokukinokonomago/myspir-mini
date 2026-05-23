<div class="mx-auto max-w-md rounded-[2rem] border border-line bg-white p-8 shadow-sm">
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium uppercase tracking-[0.2em] text-brand">Admin</p>
        <h1 class="text-3xl font-semibold tracking-tight text-ink">管理者ログイン</h1>
        <p class="mt-3 text-sm text-slate-500">空き枠管理、予約確認、Google 連携設定を行います。</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-6 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form action="/admin/login" method="POST" class="space-y-5">
        <?= csrf_field() ?>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">メールアドレス</label>
            <input type="email" name="email" value="<?= e($email ?? '') ?>" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand" required>
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">パスワード</label>
            <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none transition focus:border-brand" required>
        </div>
        <button type="submit" class="w-full rounded-2xl bg-ink px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
            ログイン
        </button>
    </form>
</div>

