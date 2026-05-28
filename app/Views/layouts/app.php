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
<?php $pageContainerClass = $pageContainerClass ?? 'max-w-7xl'; ?>
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
<div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(37,99,235,0.10),_transparent_45%),linear-gradient(180deg,_#f8fbff_0%,_#f8fafc_100%)]">
    <header class="border-b border-line bg-white/80 backdrop-blur">
        <div class="mx-auto flex <?= e($pageContainerClass) ?> items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
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
        <div class="mx-auto <?= e($pageContainerClass) ?> px-4 pt-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl px-4 py-3 text-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                <?= e($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="mx-auto <?= e($pageContainerClass) ?> px-4 py-8 sm:px-6 lg:px-8">
        <?php if (($isAdminArea ?? false) && \App\Core\Auth::check()): ?>
            <?php
            $adminNavItems = [
                ['/admin', 'ダッシュボード', 'overview'],
                ['/admin/availability-slots', '空き枠一覧', 'availability'],
                ['/admin/availability-slots/create', '空き枠追加', 'planner'],
                ['/admin/bookings', '予約一覧', 'bookings'],
                ['/admin/google', 'Google連携設定', 'google'],
            ];
            ?>
            <div class="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)] lg:items-start">
                <aside class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-white/88 p-4 shadow-[0_18px_50px_rgba(15,23,42,0.08)] backdrop-blur lg:sticky lg:top-6">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.18),_transparent_65%)]"></div>
                    <div class="relative">
                        <div class="mb-4 rounded-[1.5rem] border border-slate-100 bg-slate-50/80 px-4 py-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.24em] text-slate-400">Admin Console</p>
                            <div class="mt-2 flex items-center gap-2 text-sm font-semibold text-ink">
                                <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                管理メニュー
                            </div>
                        </div>
                        <nav class="space-y-2 text-sm">
                            <?php foreach ($adminNavItems as [$href, $label, $matchKey]): ?>
                                <?php
                                $isActive = $currentPath === $href
                                    || ($matchKey === 'availability' && $currentPath === '/admin/availability-slots')
                                    || ($matchKey === 'planner' && $currentPath === '/admin/availability-slots/create')
                                    || ($matchKey === 'bookings' && str_starts_with($currentPath, '/admin/bookings'))
                                    || ($matchKey === 'google' && str_starts_with($currentPath, '/admin/google'))
                                    || ($matchKey === 'overview' && $currentPath === '/admin');
                                ?>
                                <a
                                    href="<?= e($href) ?>"
                                    class="group flex items-center justify-between rounded-[1.35rem] px-4 py-3 transition <?= $isActive ? 'bg-ink text-white shadow-[0_12px_24px_rgba(15,23,42,0.18)]' : 'text-slate-700 hover:bg-mist hover:text-ink' ?>"
                                >
                                    <span class="font-medium"><?= e($label) ?></span>
                                    <span class="text-[10px] uppercase tracking-[0.18em] <?= $isActive ? 'text-slate-300' : 'text-slate-300 group-hover:text-brand' ?>">
                                        <?= $isActive ? 'open' : 'go' ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
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
