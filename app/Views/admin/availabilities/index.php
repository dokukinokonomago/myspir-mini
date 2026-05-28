<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm uppercase tracking-[0.2em] text-brand">Availability</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">空き枠一覧</h1>
            <p class="mt-3 text-sm text-slate-500">一覧確認に加えて、週カレンダーから直感的に空き枠を追加できます。</p>
        </div>
        <a href="/admin/availability-slots/create" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">週カレンダーで追加</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-line bg-white shadow-sm">
        <form action="/admin/availability-slots/delete-selected" method="POST" id="slot-bulk-action-form" class="hidden">
            <?= csrf_field() ?>
        </form>
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-ink">選択アクション</p>
                <p class="mt-1 text-xs text-slate-500">選択した未予約枠に対して、非表示・予約済・削除をまとめて実行できます。</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600">
                    <input type="checkbox" id="select-all-slots-checkbox" class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/20">
                    全選択
                </label>
                <span id="selected-slot-count" class="rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-500">0件選択中</span>
                <button type="submit" id="hide-selected-slots-button" formaction="/admin/availability-slots/hide-selected" form="slot-bulk-action-form" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400" disabled>
                    非表示
                </button>
                <button type="button" id="reserve-selected-slots-button" class="rounded-full bg-ink px-4 py-2 text-xs font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300" disabled>
                    予約済
                </button>
                <button type="submit" id="delete-selected-slots-button" formaction="/admin/availability-slots/delete-selected" form="slot-bulk-action-form" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400" disabled>
                    削除
                </button>
            </div>
        </div>
        <div id="selected-slot-warning" class="hidden border-b border-rose-100 bg-rose-50/80 px-5 py-3 text-sm font-medium text-rose-700">
            選択中の空き枠に一括操作を実行します。内容を確認して実行してください。
        </div>
        <div class="space-y-3 px-4 py-4 md:hidden">
            <?php foreach ($slots as $slot): ?>
                <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-2">
                            <div class="text-sm font-semibold text-ink">
                                <?= e(format_datetime($slot['start_datetime'])) ?>
                            </div>
                            <div class="text-xs text-slate-400">
                                <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?>まで / <?= e((string) $slot['duration_minutes']) ?>分
                            </div>
                        </div>
                        <?php if (!$slot['booking_id']): ?>
                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600">
                                <input
                                    type="checkbox"
                                    name="slot_ids[]"
                                    value="<?= e((string) $slot['id']) ?>"
                                    form="slot-bulk-action-form"
                                    data-slot-id="<?= e((string) $slot['id']) ?>"
                                    data-slot-start="<?= e(format_datetime($slot['start_datetime'])) ?>"
                                    data-slot-end="<?= e(format_datetime($slot['end_datetime'], 'H:i')) ?>"
                                    data-slot-memo="<?= e($slot['memo'] ?: '') ?>"
                                    data-slot-active="<?= (int) $slot['is_active'] === 1 ? '1' : '0' ?>"
                                    class="slot-select-checkbox h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-200"
                                >
                                選択
                            </label>
                        <?php else: ?>
                            <span class="rounded-full bg-rose-50 px-3 py-2 text-[11px] font-medium text-rose-700">予約済み</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-medium <?= (int) $slot['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                            <?= (int) $slot['is_active'] === 1 ? '表示中' : '非表示' ?>
                        </span>
                        <?php if ($slot['booking_id']): ?>
                            <a href="/admin/bookings/<?= e((string) $slot['booking_id']) ?>" class="rounded-full bg-brand/10 px-3 py-1 text-xs font-medium text-brand">
                                <?= e($slot['client_name']) ?> 様
                            </a>
                        <?php else: ?>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">未予約</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3 text-sm text-slate-600">
                        <span class="font-medium text-slate-500">メモ:</span>
                        <?= e($slot['memo'] ?: '-') ?>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/toggle" method="POST">
                            <?= csrf_field() ?>
                            <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                <?= (int) $slot['is_active'] === 1 ? '非表示' : '表示' ?>
                            </button>
                        </form>
                        <?php if (!$slot['booking_id']): ?>
                            <button
                                type="button"
                                class="list-reserve-trigger rounded-full bg-ink px-4 py-2 text-xs font-medium text-white transition hover:bg-slate-800"
                                data-slot-id="<?= e((string) $slot['id']) ?>"
                                data-slot-start="<?= e(format_datetime($slot['start_datetime'])) ?>"
                                data-slot-end="<?= e(format_datetime($slot['end_datetime'], 'H:i')) ?>"
                                data-slot-memo="<?= e($slot['memo'] ?: '') ?>"
                                data-slot-active="<?= (int) $slot['is_active'] === 1 ? '1' : '0' ?>"
                            >
                                予約済
                            </button>
                            <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/delete" method="POST" onsubmit="return confirm('この空き枠を削除しますか？');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_to" value="index">
                                <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                    削除
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/delete" method="POST" onsubmit="return confirm('予約済みの予定を削除します。Google カレンダー予定も削除されます。実行しますか？');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return_to" value="index">
                                <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                    削除
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$slots): ?>
                <div class="rounded-3xl border border-slate-200 bg-white px-4 py-8 text-center text-slate-500 shadow-sm">空き枠はまだありません。</div>
            <?php endif; ?>
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-medium">選択</th>
                        <th class="px-5 py-4 font-medium">日時</th>
                        <th class="px-5 py-4 font-medium">面談時間</th>
                        <th class="px-5 py-4 font-medium">状態</th>
                        <th class="px-5 py-4 font-medium">予約</th>
                        <th class="px-5 py-4 font-medium">メモ</th>
                        <th class="px-5 py-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($slots as $slot): ?>
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <?php if (!$slot['booking_id']): ?>
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-500">
                                        <input
                                            type="checkbox"
                                            name="slot_ids[]"
                                            value="<?= e((string) $slot['id']) ?>"
                                            form="slot-bulk-action-form"
                                            data-slot-id="<?= e((string) $slot['id']) ?>"
                                            data-slot-start="<?= e(format_datetime($slot['start_datetime'])) ?>"
                                            data-slot-end="<?= e(format_datetime($slot['end_datetime'], 'H:i')) ?>"
                                            data-slot-memo="<?= e($slot['memo'] ?: '') ?>"
                                            data-slot-active="<?= (int) $slot['is_active'] === 1 ? '1' : '0' ?>"
                                            class="slot-select-checkbox h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-200"
                                        >
                                        選択
                                    </label>
                                <?php else: ?>
                                    <span class="text-xs text-rose-400">削除</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <?= e(format_datetime($slot['start_datetime'])) ?><br>
                                <span class="text-slate-400">- <?= e(format_datetime($slot['end_datetime'], 'H:i')) ?></span>
                            </td>
                            <td class="px-5 py-4 text-slate-700"><?= e((string) $slot['duration_minutes']) ?>分</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-medium <?= (int) $slot['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= (int) $slot['is_active'] === 1 ? '表示中' : '非表示' ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <?php if ($slot['booking_id']): ?>
                                    <a href="/admin/bookings/<?= e((string) $slot['booking_id']) ?>" class="font-medium text-brand"><?= e($slot['client_name']) ?> 様</a>
                                    <div class="text-xs text-slate-500"><?= e($slot['client_email']) ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400">未予約</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-slate-600"><?= e($slot['memo'] ?: '-') ?></td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/toggle" method="POST">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                            <?= (int) $slot['is_active'] === 1 ? '非表示' : '表示' ?>
                                        </button>
                                    </form>
                                    <?php if (!$slot['booking_id']): ?>
                                        <button
                                            type="button"
                                            class="list-reserve-trigger rounded-full bg-ink px-4 py-2 text-xs font-medium text-white transition hover:bg-slate-800"
                                            data-slot-id="<?= e((string) $slot['id']) ?>"
                                            data-slot-start="<?= e(format_datetime($slot['start_datetime'])) ?>"
                                            data-slot-end="<?= e(format_datetime($slot['end_datetime'], 'H:i')) ?>"
                                            data-slot-memo="<?= e($slot['memo'] ?: '') ?>"
                                            data-slot-active="<?= (int) $slot['is_active'] === 1 ? '1' : '0' ?>"
                                        >
                                            予約済
                                        </button>
                                        <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/delete" method="POST" onsubmit="return confirm('この空き枠を削除しますか？');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return_to" value="index">
                                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                削除
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/delete" method="POST" onsubmit="return confirm('予約済みの予定を削除します。Google カレンダー予定も削除されます。実行しますか？');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="return_to" value="index">
                                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                削除
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$slots): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-slate-500">空き枠はまだありません。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="list-reserve-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/35 px-4 py-6">
    <div class="mx-auto my-6 max-h-[calc(100vh-3rem)] max-w-2xl overflow-y-auto rounded-[2rem] bg-white p-6 shadow-2xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-brand">Manual Booking</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink">空き枠を予約済みにする</h2>
                <p id="list-reserve-summary" class="mt-3 text-sm leading-6 text-slate-500">一覧から直接予約情報を入力して、予約済みに変更できます。</p>
            </div>
            <button type="button" id="list-reserve-close" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-600">閉じる</button>
        </div>

        <form action="/admin/availability-slots/reserve-selected" method="POST" id="list-reserve-form" class="space-y-5">
            <?= csrf_field() ?>
            <div id="list-reserve-slot-ids"></div>
            <input type="hidden" name="bookings_payload_json" id="list-reserve-payload-json" value="">
            <input type="hidden" name="return_to" value="index">
            <input type="hidden" name="week" value="">

            <div class="rounded-3xl bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Selected Slot</p>
                        <p id="list-reserve-slot-pill" class="mt-2 text-lg font-semibold text-ink">未選択</p>
                    </div>
                    <span id="list-reserve-slot-count" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500">0件</span>
                </div>
                <p id="list-reserve-summary" class="mt-2 text-sm leading-6 text-slate-500">一覧から直接予約情報を入力して、予約済みに変更できます。</p>
            </div>

            <div class="rounded-3xl border border-slate-200 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Slot Tabs</p>
                        <p class="mt-2 text-sm text-slate-500">選択した枠ごとにタブを切り替えて、内容を個別入力できます。</p>
                    </div>
                </div>
                <div id="list-reserve-tab-list" class="mt-4 flex flex-wrap gap-2"></div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <span class="font-medium text-ink">現在の枠:</span>
                        <span id="list-reserve-active-label">未選択</span>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label for="list-reserve-client-name" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">氏名</label>
                    <input type="text" id="list-reserve-client-name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="山田 太郎">
                </div>
                <div>
                    <label for="list-reserve-company-name" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">会社名</label>
                    <input type="text" id="list-reserve-company-name" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="株式会社サンプル">
                </div>
                <div>
                    <label for="list-reserve-client-email" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">メールアドレス</label>
                    <input type="email" id="list-reserve-client-email" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="client@example.com">
                </div>
                <div>
                    <label for="list-reserve-client-phone" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">電話番号</label>
                    <input type="text" id="list-reserve-client-phone" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="090-1234-5678">
                </div>
                <div class="flex items-center">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" id="list-reserve-slot-active" class="h-4 w-4 rounded border-slate-300 text-brand" checked>
                        公開状態を維持
                    </label>
                </div>
                <div class="sm:col-span-2">
                    <label for="list-reserve-slot-memo" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">空き枠メモ</label>
                    <textarea id="list-reserve-slot-memo" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="この枠のメモ"></textarea>
                </div>
                <div class="sm:col-span-2">
                    <label for="list-reserve-message" class="mb-2 block text-xs font-medium uppercase tracking-[0.2em] text-slate-400">相談内容 / メモ</label>
                    <textarea id="list-reserve-message" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand" placeholder="予約内容やメモを入力"></textarea>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" id="list-reserve-cancel" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-100">キャンセル</button>
                <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">予約済</button>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const bulkForm = document.getElementById('slot-bulk-action-form');
        const selectAllCheckbox = document.getElementById('select-all-slots-checkbox');
        const hideSelectedButton = document.getElementById('hide-selected-slots-button');
        const reserveSelectedButton = document.getElementById('reserve-selected-slots-button');
        const deleteSelectedButton = document.getElementById('delete-selected-slots-button');
        const selectedCountLabel = document.getElementById('selected-slot-count');
        const selectedSlotWarning = document.getElementById('selected-slot-warning');
        const checkboxes = Array.from(document.querySelectorAll('.slot-select-checkbox'));
        const listReserveModal = document.getElementById('list-reserve-modal');
        const listReserveClose = document.getElementById('list-reserve-close');
        const listReserveCancel = document.getElementById('list-reserve-cancel');
        const listReserveForm = document.getElementById('list-reserve-form');
        const listReserveSlotIds = document.getElementById('list-reserve-slot-ids');
        const listReservePayloadJson = document.getElementById('list-reserve-payload-json');
        const listReserveSlotPill = document.getElementById('list-reserve-slot-pill');
        const listReserveSlotCount = document.getElementById('list-reserve-slot-count');
        const listReserveSummary = document.getElementById('list-reserve-summary');
        const listReserveTabList = document.getElementById('list-reserve-tab-list');
        const listReserveActiveLabel = document.getElementById('list-reserve-active-label');
        const listReserveSlotMemo = document.getElementById('list-reserve-slot-memo');
        const listReserveSlotActive = document.getElementById('list-reserve-slot-active');
        const listReserveClientName = document.getElementById('list-reserve-client-name');
        const listReserveCompanyName = document.getElementById('list-reserve-company-name');
        const listReserveClientEmail = document.getElementById('list-reserve-client-email');
        const listReserveClientPhone = document.getElementById('list-reserve-client-phone');
        const listReserveMessage = document.getElementById('list-reserve-message');
        const listReserveTriggers = Array.from(document.querySelectorAll('.list-reserve-trigger'));
        let reserveTabState = [];
        let activeReserveTabIndex = 0;

        if (!bulkForm || !selectAllCheckbox || !hideSelectedButton || !reserveSelectedButton || !deleteSelectedButton || !selectedCountLabel || !selectedSlotWarning) {
            return;
        }

        function getSelectedSlotIds() {
            return Array.from(new Set(
                checkboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.dataset.slotId || checkbox.value)
            ));
        }

        function getSelectedSlots() {
            const selectedIds = new Set(getSelectedSlotIds());
            const uniqueSlots = new Map();

            checkboxes
                .filter((checkbox) => selectedIds.has(checkbox.dataset.slotId || checkbox.value))
                .forEach((checkbox) => {
                    const slotId = checkbox.dataset.slotId || checkbox.value;
                    if (uniqueSlots.has(slotId)) {
                        return;
                    }

                    uniqueSlots.set(slotId, {
                        id: slotId,
                        start: checkbox.dataset.slotStart || '',
                        end: checkbox.dataset.slotEnd || '',
                        memo: checkbox.dataset.slotMemo || '',
                        isActive: (checkbox.dataset.slotActive || '1') === '1',
                    });
                });

            return Array.from(uniqueSlots.values());
        }

        function getUniqueSlotCount() {
            return new Set(checkboxes.map((checkbox) => checkbox.dataset.slotId || checkbox.value)).size;
        }

        function syncSelectionState() {
            const selectedCount = getSelectedSlotIds().length;
            const allSelected = selectedCount > 0 && selectedCount === getUniqueSlotCount();

            selectedCountLabel.textContent = `${selectedCount}件選択中`;
            hideSelectedButton.disabled = selectedCount === 0;
            reserveSelectedButton.disabled = selectedCount === 0;
            deleteSelectedButton.disabled = selectedCount === 0;
            selectAllCheckbox.checked = allSelected;
            selectAllCheckbox.indeterminate = selectedCount > 0 && !allSelected;
            selectedSlotWarning.classList.toggle('hidden', selectedCount === 0);
            selectedCountLabel.className = selectedCount > 0
                ? 'rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700'
                : 'rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-500';
        }

        selectAllCheckbox.addEventListener('change', () => {
            const shouldSelectAll = selectAllCheckbox.checked;
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldSelectAll;
            });
            syncSelectionState();
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectionState);
        });

        bulkForm.addEventListener('submit', (event) => {
            const selectedCount = getSelectedSlotIds().length;

            if (selectedCount === 0) {
                event.preventDefault();
                return;
            }

            const submitter = event.submitter;

            if (submitter?.id === 'hide-selected-slots-button') {
                if (!window.confirm(`選択した ${selectedCount} 件の空き枠を非表示にします。実行しますか？`)) {
                    event.preventDefault();
                }
                return;
            }

            if (submitter?.id === 'delete-selected-slots-button') {
                if (!window.confirm(`選択した ${selectedCount} 件の空き枠を削除します。\nこの操作は元に戻せません。実行しますか？`)) {
                    event.preventDefault();
                }
            }
        });

        function closeReserveModal() {
            if (!listReserveModal) {
                return;
            }

            listReserveModal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function persistActiveReserveTab() {
            if (!reserveTabState[activeReserveTabIndex]) {
                return;
            }

            reserveTabState[activeReserveTabIndex] = {
                ...reserveTabState[activeReserveTabIndex],
                clientName: listReserveClientName?.value.trim() || '',
                companyName: listReserveCompanyName?.value.trim() || '',
                clientEmail: listReserveClientEmail?.value.trim() || '',
                clientPhone: listReserveClientPhone?.value.trim() || '',
                slotMemo: listReserveSlotMemo?.value.trim() || '',
                message: listReserveMessage?.value.trim() || '',
                isActive: Boolean(listReserveSlotActive?.checked),
            };
        }

        function renderReserveTabs() {
            if (!listReserveTabList) {
                return;
            }

            listReserveTabList.innerHTML = '';

            reserveTabState.forEach((slot, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = index === activeReserveTabIndex
                    ? 'rounded-full bg-ink px-4 py-2 text-xs font-medium text-white'
                    : 'rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100';
                button.textContent = `枠${index + 1}`;
                button.addEventListener('click', () => {
                    persistActiveReserveTab();
                    activeReserveTabIndex = index;
                    hydrateActiveReserveTab();
                });
                listReserveTabList.appendChild(button);
            });
        }

        function hydrateActiveReserveTab() {
            const slot = reserveTabState[activeReserveTabIndex];
            if (!slot) {
                return;
            }

            if (listReserveActiveLabel) {
                listReserveActiveLabel.textContent = `${slot.start} - ${slot.end}`;
            }
            if (listReserveClientName) {
                listReserveClientName.value = slot.clientName || '';
            }
            if (listReserveCompanyName) {
                listReserveCompanyName.value = slot.companyName || '';
            }
            if (listReserveClientEmail) {
                listReserveClientEmail.value = slot.clientEmail || '';
            }
            if (listReserveClientPhone) {
                listReserveClientPhone.value = slot.clientPhone || '';
            }
            if (listReserveSlotMemo) {
                listReserveSlotMemo.value = slot.slotMemo || '';
            }
            if (listReserveMessage) {
                listReserveMessage.value = slot.message || '';
            }
            if (listReserveSlotActive) {
                listReserveSlotActive.checked = Boolean(slot.isActive);
            }

            renderReserveTabs();
        }

        function syncReserveSlotIds(slotIds) {
            if (!listReserveSlotIds) {
                return;
            }

            listReserveSlotIds.innerHTML = '';
            slotIds.forEach((slotId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'slot_ids[]';
                input.value = slotId;
                listReserveSlotIds.appendChild(input);
            });
        }

        function buildReservePayload() {
            persistActiveReserveTab();

            return reserveTabState.map((slot) => ({
                slot_id: slot.id,
                client_name: slot.clientName || '',
                company_name: slot.companyName || '',
                client_email: slot.clientEmail || '',
                client_phone: slot.clientPhone || '',
                slot_memo: slot.slotMemo || '',
                message: slot.message || '',
                is_active: slot.isActive ? 1 : 0,
            }));
        }

        function validateReservePayload(payload) {
            for (let index = 0; index < payload.length; index += 1) {
                const item = payload[index];
                if (!item.client_name.trim()) {
                    activeReserveTabIndex = index;
                    hydrateActiveReserveTab();
                    window.alert(`枠${index + 1} の氏名を入力してください。`);
                    listReserveClientName?.focus();
                    return false;
                }

                if (item.client_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(item.client_email)) {
                    activeReserveTabIndex = index;
                    hydrateActiveReserveTab();
                    window.alert(`枠${index + 1} のメールアドレス形式が不正です。`);
                    listReserveClientEmail?.focus();
                    return false;
                }
            }

            return true;
        }

        function openReserveModal(slots) {
            if (!listReserveModal || !listReserveSlotPill || !listReserveSlotMemo || !listReserveSlotActive || slots.length === 0) {
                return;
            }

            const firstSlot = slots[0];
            const slotIds = slots.map((slot) => slot.id);
            syncReserveSlotIds(slotIds);
            reserveTabState = slots.map((slot) => ({
                id: slot.id,
                start: slot.start,
                end: slot.end,
                clientName: '',
                companyName: '',
                clientEmail: '',
                clientPhone: '',
                slotMemo: slot.memo || '',
                message: '',
                isActive: Boolean(slot.isActive),
            }));
            activeReserveTabIndex = 0;
            listReserveSlotPill.textContent = slots.length === 1
                ? `${firstSlot.start} - ${firstSlot.end}`
                : `${slots.length}件の空き枠を選択中`;
            if (listReserveSlotCount) {
                listReserveSlotCount.textContent = `${slots.length}件`;
            }
            if (listReserveSummary) {
                listReserveSummary.textContent = slots.length === 1
                    ? 'この空き枠の予約情報を入力して、予約済みに変更します。'
                    : '選択した枠ごとにタブを切り替えて、予約情報を個別入力できます。';
            }
            if (listReservePayloadJson) {
                listReservePayloadJson.value = '';
            }
            hydrateActiveReserveTab();
            listReserveClientName?.focus();

            listReserveModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        listReserveTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => openReserveModal([{
                id: trigger.dataset.slotId || '',
                start: trigger.dataset.slotStart || '',
                end: trigger.dataset.slotEnd || '',
                memo: trigger.dataset.slotMemo || '',
                isActive: (trigger.dataset.slotActive || '1') === '1',
            }]));
        });

        reserveSelectedButton.addEventListener('click', () => {
            const selectedSlots = getSelectedSlots();

            if (selectedSlots.length === 0) {
                return;
            }

            openReserveModal(selectedSlots);
        });

        [listReserveClose, listReserveCancel].forEach((element) => {
            if (!element) {
                return;
            }
            element.addEventListener('click', closeReserveModal);
        });

        if (listReserveModal) {
            listReserveModal.addEventListener('click', (event) => {
                if (event.target === listReserveModal) {
                    closeReserveModal();
                }
            });
        }

        listReserveForm?.addEventListener('submit', (event) => {
            const payload = buildReservePayload();
            if (!validateReservePayload(payload)) {
                event.preventDefault();
                return;
            }

            if (listReservePayloadJson) {
                listReservePayloadJson.value = JSON.stringify(payload);
            }
        });

        syncSelectionState();
    })();
</script>
