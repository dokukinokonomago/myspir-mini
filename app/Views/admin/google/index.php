<div class="space-y-6">
    <div>
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Google Calendar</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">Google連携設定</h1>
    </div>

    <?php
    $clientIdConfigured = (bool) env_value('GOOGLE_CLIENT_ID');
    $clientSecretConfigured = (bool) env_value('GOOGLE_CLIENT_SECRET');
    $redirectUri = env_value('GOOGLE_REDIRECT_URI', '');
    $appUrl = env_value('APP_URL', '');
    ?>

    <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-ink">連携状態</h2>
                <p class="mt-2 text-sm text-slate-500">予約確定時に Google カレンダーへ予定を作成し、Meet リンクを自動発行します。</p>
            </div>
            <div class="rounded-full px-4 py-2 text-sm font-medium <?= $calendarInfo ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                <?= $calendarInfo ? '接続済み' : '未接続' ?>
            </div>
        </div>

        <?php if (!$isConfigured): ?>
            <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50/70 p-5">
                <h3 class="text-base font-semibold text-amber-900">Google OAuth の事前設定が未完了です</h3>
                <p class="mt-2 text-sm leading-6 text-amber-800">
                    先に Google Cloud Console で OAuth クライアントを作成し、`.env` に Client ID / Secret を入れてください。
                </p>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-2xl bg-white/80 p-4 text-sm">
                        <p class="font-medium text-slate-800">設定ファイル</p>
                        <p class="mt-2 break-all text-slate-600">/Users/dinho/Desktop/myspir_mini/.env</p>
                    </div>
                    <div class="rounded-2xl bg-white/80 p-4 text-sm">
                        <p class="font-medium text-slate-800">承認済みリダイレクト URI</p>
                        <p class="mt-2 break-all text-slate-600"><?= e($redirectUri) ?></p>
                    </div>
                </div>
                <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-amber-900">
                    <li>Google Cloud Console で Calendar API を有効化</li>
                    <li>OAuth 同意画面を設定</li>
                    <li>Web アプリの OAuth クライアントを作成</li>
                    <li>承認済みリダイレクト URI に <span class="break-all font-medium"><?= e($redirectUri) ?></span> を登録</li>
                    <li>`.env` に `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` を保存</li>
                    <li>この画面を再読込してから「Google と連携する」を押下</li>
                </ol>
                <div class="mt-4 rounded-2xl bg-white/80 p-4 text-sm text-slate-700">
                    <p>ローカル URL: <?= e($appUrl) ?></p>
                    <p class="mt-2">現在の状態: Client ID <?= $clientIdConfigured ? '設定済み' : '未設定' ?> / Client Secret <?= $clientSecretConfigured ? '設定済み' : '未設定' ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($connectionError): ?>
            <div class="mt-4 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-700">
                連携確認中にエラーが発生しました: <?= e($connectionError) ?>
            </div>
        <?php endif; ?>

        <dl class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Client ID / Secret</dt>
                <dd class="mt-2 text-sm text-slate-700"><?= $isConfigured ? '設定済み' : '未設定' ?></dd>
                <dd class="mt-1 text-xs text-slate-500">Client ID: <?= $clientIdConfigured ? '設定済み' : '未設定' ?> / Secret: <?= $clientSecretConfigured ? '設定済み' : '未設定' ?></dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Redirect URI</dt>
                <dd class="mt-2 break-all text-sm text-slate-700"><?= e($redirectUri) ?></dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">トークン有効期限</dt>
                <dd class="mt-2 text-sm text-slate-700"><?= e($user['google_token_expires_at'] ?? '-') ?></dd>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Primary Calendar</dt>
                <dd class="mt-2 text-sm text-slate-700"><?= e($calendarInfo['summary'] ?? '-') ?></dd>
            </div>
        </dl>

        <?php if ($calendarInfo): ?>
            <div class="mt-6 rounded-2xl bg-mist p-4 text-sm text-slate-700">
                <p>Calendar ID: <?= e($calendarInfo['id'] ?? '-') ?></p>
                <p class="mt-2">許可された会議種別: <?= e(implode(', ', $calendarInfo['conferenceProperties']['allowedConferenceSolutionTypes'] ?? ['-'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="mt-6 flex flex-wrap gap-3">
            <?php if (!$calendarInfo): ?>
                <form action="/admin/google/connect" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800" <?= $isConfigured ? '' : 'disabled' ?>>
                        Google と連携する
                    </button>
                </form>
            <?php else: ?>
                <form action="/admin/google/disconnect" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        連携を解除する
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
