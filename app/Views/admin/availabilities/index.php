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
        <form action="/admin/availability-slots/delete-selected" method="POST" id="slot-bulk-delete-form" class="hidden">
            <?= csrf_field() ?>
        </form>
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-ink">選択削除</p>
                <p class="mt-1 text-xs text-slate-500">未予約の空き枠だけチェックできます。予約済み枠は選択できません。</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="select-all-slots-button" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                    全てを選択
                </button>
                <span id="selected-slot-count" class="rounded-full bg-slate-100 px-4 py-2 text-xs font-medium text-slate-500">0件選択中</span>
                <button type="submit" id="delete-selected-slots-button" form="slot-bulk-delete-form" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400" disabled>
                    選択した空き枠を削除
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
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
                                            form="slot-bulk-delete-form"
                                            class="slot-select-checkbox h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-200"
                                        >
                                        選択
                                    </label>
                                <?php else: ?>
                                    <span class="text-xs text-slate-300">対象外</span>
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
                                            <?= (int) $slot['is_active'] === 1 ? '非表示にする' : '表示する' ?>
                                        </button>
                                    </form>
                                    <?php if (!$slot['booking_id']): ?>
                                        <form action="/admin/availability-slots/<?= e((string) $slot['id']) ?>/delete" method="POST" onsubmit="return confirm('この空き枠を削除しますか？');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-medium text-rose-600 transition hover:bg-rose-50">
                                                削除
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="rounded-full bg-slate-100 px-4 py-2 text-[11px] font-medium text-slate-400">予約済みで削除不可</span>
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

<script>
    (() => {
        const bulkForm = document.getElementById('slot-bulk-delete-form');
        const selectAllButton = document.getElementById('select-all-slots-button');
        const deleteSelectedButton = document.getElementById('delete-selected-slots-button');
        const selectedCountLabel = document.getElementById('selected-slot-count');
        const checkboxes = Array.from(document.querySelectorAll('.slot-select-checkbox'));

        if (!bulkForm || !selectAllButton || !deleteSelectedButton || !selectedCountLabel) {
            return;
        }

        function syncSelectionState() {
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const allSelected = selectedCount > 0 && selectedCount === checkboxes.length;

            selectedCountLabel.textContent = `${selectedCount}件選択中`;
            deleteSelectedButton.disabled = selectedCount === 0;
            selectAllButton.textContent = allSelected ? '全てを解除' : '全てを選択';
        }

        selectAllButton.addEventListener('click', () => {
            const shouldSelectAll = checkboxes.some((checkbox) => !checkbox.checked);
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldSelectAll;
            });
            syncSelectionState();
        });

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectionState);
        });

        bulkForm.addEventListener('submit', (event) => {
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

            if (selectedCount === 0) {
                event.preventDefault();
                return;
            }

            if (!window.confirm(`選択した ${selectedCount} 件の空き枠を削除しますか？`)) {
                event.preventDefault();
            }
        });

        syncSelectionState();
    })();
</script>
