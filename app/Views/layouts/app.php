<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? env_value('APP_NAME', 'myspir mini')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: '#0f172a',
                        mist: '#eef4ff',
                        line: '#dbe4f0',
                        brand: '#2563eb',
                        accent: '#0f766e'
                    }
                }
            }
        };
    </script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<?php $flash = flash_message(); ?>
<div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.10),_transparent_45%),linear-gradient(180deg,_#f8fbff_0%,_#f8fafc_100%)]">
    <header class="border-b border-line bg-white/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?= e(($isAdminArea ?? false) ? '/admin' : '/book') ?>" class="text-lg font-semibold tracking-tight text-ink">
                <?= e(env_value('APP_NAME', 'myspir mini')) ?>
            </a>
            <div class="flex items-center gap-3 text-sm">
                <?php if (\App\Core\Auth::check()): ?>
                    <a href="/admin" class="rounded-full px-4 py-2 text-slate-600 transition hover:bg-slate-100">管理画面</a>
                    <form action="/admin/logout" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-slate-600 transition hover:bg-slate-100">ログアウト</button>
                    </form>
                <?php else: ?>
                    <a href="/admin/login" class="rounded-full border border-slate-200 px-4 py-2 text-slate-600 transition hover:bg-slate-100">管理者ログイン</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl px-4 py-3 text-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                <?= e($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <?php if (($isAdminArea ?? false) && \App\Core\Auth::check()): ?>
            <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
                <aside class="rounded-3xl border border-line bg-white p-4 shadow-sm">
                    <nav class="space-y-2 text-sm">
                        <a href="/admin" class="block rounded-2xl px-4 py-3 text-slate-700 transition hover:bg-mist">ダッシュボード</a>
                        <a href="/admin/availability-slots" class="block rounded-2xl px-4 py-3 text-slate-700 transition hover:bg-mist">空き枠一覧</a>
                        <a href="/admin/availability-slots/create" class="block rounded-2xl px-4 py-3 text-slate-700 transition hover:bg-mist">空き枠追加</a>
                        <a href="/admin/bookings" class="block rounded-2xl px-4 py-3 text-slate-700 transition hover:bg-mist">予約一覧</a>
                        <a href="/admin/google" class="block rounded-2xl px-4 py-3 text-slate-700 transition hover:bg-mist">Google連携設定</a>
                    </nav>
                </aside>
                <section class="min-w-0">
                    <?= $content ?>
                </section>
            </div>
        <?php else: ?>
            <?= $content ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

