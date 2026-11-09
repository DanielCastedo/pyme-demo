{{--
    Método de pago con opción de pago mixto (ej. Bs 50 = Bs 20 por QR + Bs 30 en efectivo).

    Sin combinar se envía "payment_id" (un método por todo el monto). Con "Combinar métodos" se envían
    "payments[i][payment_id]" y "payments[i][amount]", que deben sumar exactamente el monto a cobrar
    (el servidor lo vuelve a validar en OrderPaymentService::parts()).

    El monto a cobrar lo da la página: con target-input (selector de un campo, ej. el monto del abono)
    o desde JS con PaymentSplit.get(el).setTarget(n). API para la página: PaymentSplit.get(el) devuelve
    { setTarget(n), isSplit(), isValid(), parts(), onChange }.
--}}
@props([
    'methods',
    'id' => 'payment-id',
    'label' => 'Método de pago',
    'selected' => null,
    'targetInput' => null,
    'target' => null,
])

@php
    $field = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white';
    $options = $methods->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'cash' => (bool) $m->is_cash, 'qr' => str_contains(mb_strtolower($m->name), 'qr')])->values();
    $oldParts = collect(old('payments', []))->values();
@endphp

<div {{ $attributes->merge(['class' => 'payment-split']) }} data-target-input="{{ $targetInput }}" data-target="{{ $target }}">
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <label for="{{ $id }}" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $label }}</label>
        <button type="button" data-split-toggle class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Combinar métodos</button>
    </div>

    <select name="payment_id" id="{{ $id }}" data-split-single class="{{ $field }}">
        @foreach ($options as $m)
            <option value="{{ $m['id'] }}" data-cash="{{ $m['cash'] ? 1 : 0 }}" @selected(old('payment_id', $selected) == $m['id'])>{{ $m['name'] }}</option>
        @endforeach
    </select>

    <div data-split-box class="hidden space-y-2">
        <div data-split-rows class="space-y-2"></div>
        <div class="flex items-center justify-between gap-2">
            <button type="button" data-split-add class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">+ Agregar otro método</button>
            <span data-split-status class="text-[11px] font-semibold"></span>
        </div>
    </div>

    <script type="application/json" data-split-methods>@json($options)</script>
    <script type="application/json" data-split-old>@json($oldParts)</script>
</div>

@once
    <script>
        // Pago mixto: ver resources/views/components/ui/payment-split.blade.php
        window.PaymentSplit = window.PaymentSplit || (function () {
            const round2 = (n) => Math.round((Number(n) + Number.EPSILON) * 100) / 100;
            const money = (n) => Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const fieldCls = 'h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white';

            function create(el) {
                const methods = JSON.parse(el.querySelector('[data-split-methods]').textContent);
                const single = el.querySelector('[data-split-single]');
                const box = el.querySelector('[data-split-box]');
                const rowsEl = el.querySelector('[data-split-rows]');
                const status = el.querySelector('[data-split-status]');
                const toggle = el.querySelector('[data-split-toggle]');
                const targetInput = el.dataset.targetInput ? document.querySelector(el.dataset.targetInput) : null;
                let target = el.dataset.target !== '' ? round2(el.dataset.target) : null;
                let split = false;
                const rows = [];

                const api = {
                    onChange: null,
                    // No avisa a onChange: la página suele llamarlo desde su propio refresco.
                    setTarget(n) { target = n === null ? null : round2(n); update(false); },
                    isSplit: () => split,
                    parts() {
                        return split
                            ? rows.map((r) => ({method: methods.find((m) => String(m.id) === r.select.value), amount: round2(r.amount.value || 0)})).filter((p) => p.method && p.amount > 0)
                            : (() => { const m = methods.find((x) => String(x.id) === single.value); return m ? [{method: m, amount: target ?? 0}] : []; })();
                    },
                    isValid() {
                        if (!split) return true;
                        const sum = round2(rows.reduce((s, r) => s + (Number(r.amount.value) || 0), 0));
                        return target !== null && rows.every((r) => r.select.value) && Math.abs(sum - target) < 0.005;
                    },
                };

                function reindex() {
                    rows.forEach((r, i) => {
                        r.select.name = `payments[${i}][payment_id]`;
                        r.amount.name = `payments[${i}][amount]`;
                        r.remove.classList.toggle('invisible', rows.length <= 2);
                    });
                }

                function addRow(methodId, amount) {
                    const wrap = document.createElement('div');
                    wrap.className = 'flex items-center gap-2';
                    const select = document.createElement('select');
                    select.className = fieldCls + ' flex-1 min-w-0';
                    methods.forEach((m) => {
                        const o = document.createElement('option');
                        o.value = m.id; o.textContent = m.name;
                        select.appendChild(o);
                    });
                    if (methodId) select.value = methodId;
                    const input = document.createElement('input');
                    input.type = 'number'; input.step = '0.01'; input.min = '0'; input.placeholder = '0.00';
                    input.className = fieldCls + ' w-28 text-right';
                    input.value = amount ?? '';
                    const remove = document.createElement('button');
                    remove.type = 'button'; remove.innerHTML = '&times;'; remove.title = 'Quitar';
                    remove.className = 'text-gray-400 hover:text-red-600 text-lg leading-none px-1';
                    wrap.append(select, input, remove);
                    rowsEl.appendChild(wrap);

                    const row = {wrap, select, amount: input, remove};
                    rows.push(row);
                    // Con dos métodos, al escribir el monto de uno el otro se completa con lo que falta.
                    input.addEventListener('input', () => {
                        if (rows.length === 2 && target !== null) {
                            const other = rows.find((r) => r !== row);
                            other.amount.value = Math.max(0, round2(target - (Number(input.value) || 0))).toFixed(2);
                        }
                        update();
                    });
                    select.addEventListener('change', update);
                    remove.addEventListener('click', () => {
                        if (rows.length <= 2) return;
                        rows.splice(rows.indexOf(row), 1);
                        wrap.remove();
                        reindex(); update();
                    });
                    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
                    reindex();
                }

                function setSplit(on, initial = null) {
                    split = on;
                    single.disabled = on;
                    single.classList.toggle('hidden', on);
                    box.classList.toggle('hidden', !on);
                    toggle.textContent = on ? 'Un solo método' : 'Combinar métodos';
                    rows.splice(0).forEach((r) => r.wrap.remove());
                    if (on) {
                        if (initial && initial.length) {
                            initial.forEach((p) => addRow(p.payment_id, p.amount));
                        } else {
                            // Arranca con el método elegido por todo el monto y un segundo método en 0.
                            const second = methods.find((m) => String(m.id) !== single.value);
                            addRow(single.value, target !== null ? target.toFixed(2) : '');
                            addRow(second ? second.id : single.value, '0.00');
                        }
                    }
                    update();
                }

                function update(notify = true) {
                    if (split) {
                        const sum = round2(rows.reduce((s, r) => s + (Number(r.amount.value) || 0), 0));
                        const diff = target === null ? 0 : round2(target - sum);
                        status.textContent = target === null ? '' : (Math.abs(diff) < 0.005 ? '✓ Cubierto' : (diff > 0 ? `Falta ${money(diff)}` : `Sobra ${money(-diff)}`));
                        status.className = 'text-[11px] font-semibold ' + (Math.abs(diff) < 0.005 ? 'text-emerald-600' : 'text-red-600');
                    }
                    if (notify && typeof api.onChange === 'function') api.onChange(api);
                }

                toggle.addEventListener('click', () => setSplit(!split));
                el.querySelector('[data-split-add]').addEventListener('click', () => {
                    if (rows.length >= 5) return;
                    const sum = round2(rows.reduce((s, r) => s + (Number(r.amount.value) || 0), 0));
                    addRow(methods[0]?.id, target !== null ? Math.max(0, round2(target - sum)).toFixed(2) : '');
                    update();
                });
                single.addEventListener('change', update);

                if (targetInput) {
                    const read = () => api.setTarget(targetInput.value === '' ? null : Number(targetInput.value));
                    targetInput.addEventListener('input', read);
                    read();
                }

                // No se envía un pago mixto que no cuadra (el servidor también lo rechaza).
                el.closest('form')?.addEventListener('submit', (e) => {
                    if (split && !api.isValid()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        status.textContent = target === null ? 'Indica el monto a cobrar' : 'Los métodos deben sumar ' + money(target);
                        status.className = 'text-[11px] font-semibold text-red-600';
                    }
                }, true);

                const old = JSON.parse(el.querySelector('[data-split-old]').textContent);
                if (old.length) setSplit(true, old);

                return api;
            }

            return {
                get(el) { return el._paymentSplit || (el._paymentSplit = create(el)); },
                initAll() { document.querySelectorAll('.payment-split').forEach((el) => this.get(el)); },
            };
        })();
        document.addEventListener('DOMContentLoaded', () => window.PaymentSplit.initAll());
    </script>
@endonce
