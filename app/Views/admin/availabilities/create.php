<div class="space-y-6">
    <div>
        <p class="text-sm uppercase tracking-[0.2em] text-brand">Availability</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">空き枠追加</h1>
        <p class="mt-3 text-sm text-slate-500">日付・時間はフリックで上下スクロールし、最後に OK を押して確定します。</p>
    </div>

    <div class="rounded-3xl border border-line bg-white p-6 shadow-sm">
        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= e(implode(' ', $errors)) ?>
            </div>
        <?php endif; ?>

        <form action="/admin/availability-slots" method="POST" class="grid gap-5 md:grid-cols-2">
            <?= csrf_field() ?>

            <input type="hidden" name="date" id="date" value="<?= e($form['date'] ?? '') ?>" required>
            <input type="hidden" name="start_time" id="start_time" value="<?= e($form['start_time'] ?? '') ?>" required>
            <input type="hidden" name="end_time" id="end_time" value="<?= e($form['end_time'] ?? '') ?>" required>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">日付</label>
                <button
                    type="button"
                    data-picker-open="date"
                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left outline-none transition hover:border-brand focus:border-brand"
                >
                    <span class="text-sm text-slate-800" data-picker-label="date">日付を選択</span>
                    <span class="text-slate-400">⌄</span>
                </button>
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
                <button
                    type="button"
                    data-picker-open="start_time"
                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left outline-none transition hover:border-brand focus:border-brand"
                >
                    <span class="text-sm text-slate-800" data-picker-label="start_time">開始時間を選択</span>
                    <span class="text-slate-400">⌄</span>
                </button>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">終了時間</label>
                <button
                    type="button"
                    data-picker-open="end_time"
                    class="flex w-full items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-left outline-none transition hover:border-brand focus:border-brand"
                >
                    <span class="text-sm text-slate-800" data-picker-label="end_time">終了時間を選択</span>
                    <span class="text-slate-400">⌄</span>
                </button>
            </div>

            <div class="md:col-span-2">
                <label class="mb-3 block text-sm font-medium text-slate-700">作成方法</label>
                <div class="grid gap-3 md:grid-cols-3">
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="single" <?= checked(($form['recurrence_type'] ?? 'single') === 'single') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">1回だけ作成</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">選んだ日だけ空き枠を追加します。</p>
                        </div>
                    </label>
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="weekly" <?= checked(($form['recurrence_type'] ?? 'single') === 'weekly') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">毎週まとめて作成</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">同じ曜日・同じ時間で毎週追加します。</p>
                        </div>
                    </label>
                    <label class="rounded-3xl border border-slate-200 p-4 transition hover:border-brand">
                        <input type="radio" name="recurrence_type" value="monthly" <?= checked(($form['recurrence_type'] ?? 'single') === 'monthly') ?> class="sr-only peer">
                        <div class="rounded-2xl border border-transparent p-2 peer-checked:border-brand peer-checked:bg-mist">
                            <p class="text-sm font-semibold text-ink">毎月まとめて作成</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">同じ日付・同じ時間で毎月追加します。</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">作成回数</label>
                <input
                    type="number"
                    name="recurrence_count"
                    min="1"
                    max="24"
                    value="<?= e($form['recurrence_count'] ?? '1') ?>"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand"
                    required
                >
                <p class="mt-2 text-xs text-slate-500">例: 毎週 4 回なら、今週を含めて 4 週分を作成します。</p>
            </div>

            <label class="inline-flex items-center gap-3 text-sm text-slate-700 md:self-end">
                <input type="checkbox" name="is_active" value="1" <?= checked(($form['is_active'] ?? '1') === '1') ?> class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand">
                すぐ公開する
            </label>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">メモ</label>
                <textarea name="memo" rows="4" class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-brand"><?= e($form['memo'] ?? '') ?></textarea>
            </div>

            <div class="md:col-span-2 rounded-3xl bg-slate-50 p-5">
                <p class="text-sm font-medium text-ink">作成プレビュー</p>
                <p class="mt-2 text-sm leading-6 text-slate-500" id="recurrence-summary">
                    日付・時間・作成方法を選ぶと、ここに作成内容を表示します。
                </p>
            </div>

            <div class="md:col-span-2">
                <button type="submit" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">保存する</button>
            </div>
        </form>
    </div>
</div>

<div id="wheel-picker-modal" class="fixed inset-0 z-50 hidden bg-slate-950/30 px-4 py-6">
    <div class="mx-auto mt-auto max-w-xl rounded-[2rem] bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-brand">Wheel Picker</p>
                <h2 id="wheel-picker-title" class="mt-2 text-xl font-semibold text-ink">選択</h2>
            </div>
            <button type="button" id="wheel-picker-cancel-top" class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-500">閉じる</button>
        </div>

        <div class="relative overflow-hidden rounded-3xl bg-slate-50 px-3 py-4">
            <div class="pointer-events-none absolute inset-x-6 top-1/2 z-10 h-12 -translate-y-1/2 rounded-2xl border border-brand/20 bg-white/80 shadow-sm"></div>
            <div id="wheel-picker-columns" class="grid gap-3"></div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-3">
            <button type="button" id="wheel-picker-cancel" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-600">キャンセル</button>
            <button type="button" id="wheel-picker-ok" class="rounded-2xl bg-ink px-5 py-3 text-sm font-medium text-white">OK</button>
        </div>
    </div>
</div>

<style>
    .wheel-column {
        height: 240px;
        overflow-y: auto;
        scroll-snap-type: y mandatory;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-block: 96px;
    }

    .wheel-column::-webkit-scrollbar {
        display: none;
    }

    .wheel-option {
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        scroll-snap-align: center;
        border-radius: 16px;
        font-size: 0.95rem;
        color: #64748b;
        transition: all 0.2s ease;
    }

    .wheel-option[data-selected="true"] {
        color: #0f172a;
        font-weight: 600;
        transform: scale(1.02);
    }
</style>

<script>
    (() => {
        const hiddenInputs = {
            date: document.getElementById('date'),
            start_time: document.getElementById('start_time'),
            end_time: document.getElementById('end_time'),
        };
        const recurrenceCountInput = document.querySelector('input[name="recurrence_count"]');

        const labelNodes = {
            date: document.querySelector('[data-picker-label="date"]'),
            start_time: document.querySelector('[data-picker-label="start_time"]'),
            end_time: document.querySelector('[data-picker-label="end_time"]'),
        };

        const recurrenceSummary = document.getElementById('recurrence-summary');
        const modal = document.getElementById('wheel-picker-modal');
        const titleNode = document.getElementById('wheel-picker-title');
        const columnsNode = document.getElementById('wheel-picker-columns');
        const okButton = document.getElementById('wheel-picker-ok');
        const cancelButtons = [
            document.getElementById('wheel-picker-cancel'),
            document.getElementById('wheel-picker-cancel-top'),
        ];

        const yearBase = new Date().getFullYear();
        const activePicker = {
            field: null,
            columns: [],
        };

        const fieldConfigs = {
            date: {
                title: '日付を選択',
                columns: () => [
                    {
                        key: 'year',
                        label: '年',
                        values: Array.from({ length: 5 }, (_, index) => yearBase + index),
                    },
                    {
                        key: 'month',
                        label: '月',
                        values: Array.from({ length: 12 }, (_, index) => index + 1),
                    },
                    {
                        key: 'day',
                        label: '日',
                        values: buildDayValues(readDateSelection()),
                    },
                ],
                readValue: () => hiddenInputs.date.value,
                writeValue: (selection) => {
                    const value = `${selection.year}-${pad(selection.month)}-${pad(selection.day)}`;
                    hiddenInputs.date.value = value;
                    labelNodes.date.textContent = `${selection.year}年${selection.month}月${selection.day}日`;
                },
            },
            start_time: {
                title: '開始時間を選択',
                columns: () => buildTimeColumns(),
                readValue: () => hiddenInputs.start_time.value,
                writeValue: (selection) => {
                    const value = `${pad(selection.hour)}:${pad(selection.minute)}`;
                    hiddenInputs.start_time.value = value;
                    labelNodes.start_time.textContent = value;
                    updateSummary();
                },
            },
            end_time: {
                title: '終了時間を選択',
                columns: () => buildTimeColumns(),
                readValue: () => hiddenInputs.end_time.value,
                writeValue: (selection) => {
                    const value = `${pad(selection.hour)}:${pad(selection.minute)}`;
                    hiddenInputs.end_time.value = value;
                    labelNodes.end_time.textContent = value;
                    updateSummary();
                },
            },
        };

        document.querySelectorAll('[data-picker-open]').forEach((button) => {
            button.addEventListener('click', () => openPicker(button.dataset.pickerOpen));
        });

        cancelButtons.forEach((button) => {
            button.addEventListener('click', closePicker);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closePicker();
            }
        });

        okButton.addEventListener('click', () => {
            if (!activePicker.field) {
                return;
            }

            const field = activePicker.field;
            const config = fieldConfigs[field];
            const selection = {};

            activePicker.columns.forEach((column) => {
                selection[column.key] = getSelectedValue(column);
            });

            if (field === 'date') {
                const maxDay = buildDayValues(selection).length;
                selection.day = Math.min(selection.day, maxDay);
            }

            config.writeValue(selection);
            if (field === 'date') {
                updateSummary();
            }
            closePicker();
        });

        document.querySelectorAll('input[name="recurrence_type"], input[name="recurrence_count"]').forEach((element) => {
            element.addEventListener('change', () => {
                syncRecurrenceControls();
                updateSummary();
            });
            element.addEventListener('input', updateSummary);
        });

        initializeLabels();
        syncRecurrenceControls();
        updateSummary();

        function initializeLabels() {
            const dateValue = hiddenInputs.date.value;
            if (dateValue) {
                const [year, month, day] = dateValue.split('-').map(Number);
                labelNodes.date.textContent = `${year}年${month}月${day}日`;
            }

            ['start_time', 'end_time'].forEach((field) => {
                if (hiddenInputs[field].value) {
                    labelNodes[field].textContent = hiddenInputs[field].value;
                }
            });
        }

        function openPicker(field) {
            const config = fieldConfigs[field];
            activePicker.field = field;
            activePicker.columns = config.columns();
            titleNode.textContent = config.title;
            columnsNode.innerHTML = '';
            columnsNode.className = `grid gap-3 ${field === 'date' ? 'grid-cols-3' : 'grid-cols-2'}`;

            activePicker.columns.forEach((column) => {
                const wrapper = document.createElement('div');
                const title = document.createElement('p');
                title.className = 'mb-3 text-center text-xs font-medium uppercase tracking-[0.2em] text-slate-400';
                title.textContent = column.label;

                const scroll = document.createElement('div');
                scroll.className = 'wheel-column';
                scroll.dataset.key = column.key;

                column.values.forEach((value) => {
                    const option = document.createElement('div');
                    option.className = 'wheel-option';
                    option.dataset.value = String(value);
                    option.textContent = displayOption(field, column.key, value);
                    scroll.appendChild(option);
                });

                wrapper.appendChild(title);
                wrapper.appendChild(scroll);
                columnsNode.appendChild(wrapper);

                let scrollTimer = null;
                scroll.addEventListener('scroll', () => {
                    window.clearTimeout(scrollTimer);
                    scrollTimer = window.setTimeout(() => {
                        snapToNearest(scroll);
                        if (field === 'date' && (column.key === 'year' || column.key === 'month')) {
                            refreshDayColumn();
                        }
                    }, 90);
                });
            });

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            applyCurrentSelection(field);
        }

        function closePicker() {
            activePicker.field = null;
            activePicker.columns = [];
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function applyCurrentSelection(field) {
            if (field === 'date') {
                const selection = readDateSelection();
                setColumnSelection('year', selection.year);
                setColumnSelection('month', selection.month);
                refreshDayColumn();
                setColumnSelection('day', selection.day);
                return;
            }

            const time = readTimeSelection(field);
            setColumnSelection('hour', time.hour);
            setColumnSelection('minute', time.minute);
        }

        function refreshDayColumn() {
            const column = activePicker.columns.find((item) => item.key === 'day');
            const scroll = columnsNode.querySelector('[data-key="day"]');
            if (!column || !scroll) {
                return;
            }

            const current = getSelectedValue(column) || readDateSelection().day;
            const year = Number(getSelectedByKey('year'));
            const month = Number(getSelectedByKey('month'));
            const values = buildDayValues({ year, month });
            column.values = values;
            scroll.innerHTML = '';

            values.forEach((value) => {
                const option = document.createElement('div');
                option.className = 'wheel-option';
                option.dataset.value = String(value);
                option.textContent = displayOption('date', 'day', value);
                scroll.appendChild(option);
            });

            setColumnSelection('day', Math.min(Number(current), values.length));
        }

        function setColumnSelection(key, value) {
            const scroll = columnsNode.querySelector(`[data-key="${key}"]`);
            const option = scroll ? scroll.querySelector(`[data-value="${value}"]`) : null;
            if (!scroll || !option) {
                return;
            }

            const top = option.offsetTop - (scroll.clientHeight / 2) + (option.clientHeight / 2);
            scroll.scrollTop = top;
            markSelection(scroll, option);
        }

        function snapToNearest(scroll) {
            const options = Array.from(scroll.querySelectorAll('.wheel-option'));
            if (options.length === 0) {
                return;
            }

            const center = scroll.scrollTop + (scroll.clientHeight / 2);
            let nearest = options[0];
            let nearestDistance = Math.abs((nearest.offsetTop + nearest.clientHeight / 2) - center);

            options.forEach((option) => {
                const distance = Math.abs((option.offsetTop + option.clientHeight / 2) - center);
                if (distance < nearestDistance) {
                    nearest = option;
                    nearestDistance = distance;
                }
            });

            scroll.scrollTo({ top: nearest.offsetTop - (scroll.clientHeight / 2) + (nearest.clientHeight / 2), behavior: 'smooth' });
            markSelection(scroll, nearest);
        }

        function markSelection(scroll, activeOption) {
            scroll.querySelectorAll('.wheel-option').forEach((option) => {
                option.dataset.selected = option === activeOption ? 'true' : 'false';
            });
        }

        function getSelectedValue(column) {
            const selected = columnsNode.querySelector(`[data-key="${column.key}"] .wheel-option[data-selected="true"]`);
            if (selected) {
                return Number(selected.dataset.value);
            }

            return Number(column.values[0]);
        }

        function getSelectedByKey(key) {
            const selected = columnsNode.querySelector(`[data-key="${key}"] .wheel-option[data-selected="true"]`);
            return selected ? selected.dataset.value : null;
        }

        function readDateSelection() {
            if (!hiddenInputs.date.value) {
                const now = new Date();
                return {
                    year: now.getFullYear(),
                    month: now.getMonth() + 1,
                    day: now.getDate(),
                };
            }

            const [year, month, day] = hiddenInputs.date.value.split('-').map(Number);
            return { year, month, day };
        }

        function readTimeSelection(field) {
            const value = hiddenInputs[field].value;
            if (!value) {
                return field === 'end_time'
                    ? { hour: 11, minute: 0 }
                    : { hour: 10, minute: 0 };
            }

            const [hour, minute] = value.split(':').map(Number);
            return { hour, minute };
        }

        function buildTimeColumns() {
            return [
                {
                    key: 'hour',
                    label: '時',
                    values: Array.from({ length: 24 }, (_, index) => index),
                },
                {
                    key: 'minute',
                    label: '分',
                    values: Array.from({ length: 12 }, (_, index) => index * 5),
                },
            ];
        }

        function buildDayValues(selection) {
            const year = Number(selection.year);
            const month = Number(selection.month);
            const lastDay = new Date(year, month, 0).getDate();
            return Array.from({ length: lastDay }, (_, index) => index + 1);
        }

        function displayOption(field, key, value) {
            if (field === 'date') {
                return `${value}${key === 'year' ? '年' : key === 'month' ? '月' : '日'}`;
            }

            return key === 'hour' ? `${pad(value)}時` : `${pad(value)}分`;
        }

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function updateSummary() {
            const date = hiddenInputs.date.value;
            const startTime = hiddenInputs.start_time.value;
            const endTime = hiddenInputs.end_time.value;
            const recurrenceType = document.querySelector('input[name="recurrence_type"]:checked')?.value || 'single';
            const recurrenceCount = Number(recurrenceCountInput?.value || 1);

            if (!date || !startTime || !endTime) {
                recurrenceSummary.textContent = '日付・開始時間・終了時間を選ぶと、ここに作成内容を表示します。';
                return;
            }

            const labels = {
                single: '1回だけ',
                weekly: '毎週',
                monthly: '毎月',
            };

            const repeatText = recurrenceType === 'single'
                ? 'この日だけ 1 件作成'
                : `${labels[recurrenceType]}で ${Math.max(1, recurrenceCount)} 件作成`;

            recurrenceSummary.textContent = `${date} ${startTime} - ${endTime} を起点に、${repeatText}します。`;
        }

        function syncRecurrenceControls() {
            const recurrenceType = document.querySelector('input[name="recurrence_type"]:checked')?.value || 'single';

            if (recurrenceType === 'single') {
                recurrenceCountInput.value = '1';
                recurrenceCountInput.setAttribute('readonly', 'readonly');
                recurrenceCountInput.classList.add('bg-slate-100', 'text-slate-400');
                return;
            }

            recurrenceCountInput.removeAttribute('readonly');
            recurrenceCountInput.classList.remove('bg-slate-100', 'text-slate-400');
        }
    })();
</script>
