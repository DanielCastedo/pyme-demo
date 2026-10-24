{{-- Formulario compartido de crear/editar un gasto. Recibe: $expense (opcional), $categories, $suppliers, $paymentMethods, $cashboxes, $canCreateCategory --}}
@php
    $expense = $expense ?? null;
    $field = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
    $label = 'block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
    $err = fn ($name) => $errors->first($name);
    $currentPayment = $expense?->transaction?->payment_id;
    $currentCashbox = $expense?->transaction?->cashbox_id;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="{{ $label }}">Concepto <span class="text-red-500">*</span></label>
        <input type="text" name="concept" required maxlength="255" value="{{ old('concept', $expense?->concept) }}"
            placeholder="Ej. Pago de luz de septiembre" class="{{ $field }}">
        @if ($err('concept'))<p class="mt-1 text-xs text-red-600">{{ $err('concept') }}</p>@endif
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Categoría</label>
            @if ($canCreateCategory)
                <button type="button" id="new-category-btn" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">+ Nueva categoría</button>
            @endif
        </div>
        <select name="expense_category_id" id="category-id" class="{{ $field }}">
            <option value="">Sin categoría</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(old('expense_category_id', $expense?->expense_category_id) == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="{{ $label }}">Proveedor (opcional)</label>
        <select name="supplier_id" class="{{ $field }}">
            <option value="">Sin proveedor</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" @selected(old('supplier_id', $expense?->supplier_id) == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="{{ $label }}">Monto <span class="text-red-500">*</span></label>
        <input type="number" name="amount" required min="0.01" step="0.01" value="{{ old('amount', $expense?->amount) }}" placeholder="0.00" class="{{ $field }}">
        @if ($err('amount'))<p class="mt-1 text-xs text-red-600">{{ $err('amount') }}</p>@endif
    </div>

    <div>
        <label class="{{ $label }}">Fecha <span class="text-red-500">*</span></label>
        <input type="date" name="date" required max="{{ now()->toDateString() }}" value="{{ old('date', $expense?->date?->toDateString() ?? now()->toDateString()) }}" class="{{ $field }}">
        @if ($err('date'))<p class="mt-1 text-xs text-red-600">{{ $err('date') }}</p>@endif
    </div>

    <div>
        <label class="{{ $label }}">Método de pago <span class="text-red-500">*</span></label>
        <select name="payment_id" required class="{{ $field }}">
            @foreach ($paymentMethods as $p)
                <option value="{{ $p->id }}" @selected(old('payment_id', $currentPayment) == $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="{{ $label }}">Caja de donde sale el dinero <span class="text-red-500">*</span></label>
        <select name="cashbox_id" required class="{{ $field }}">
            @foreach ($cashboxes as $cb)
                <option value="{{ $cb->id }}" @selected(old('cashbox_id', $currentCashbox) == $cb->id)>{{ $cb->name }}{{ $cb->branch ? ' · '.$cb->branch->name : '' }} (efectivo {{ number_format((float) $cb->cash, 2) }})</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="{{ $label }}">N° de recibo / factura</label>
        <input type="text" name="receipt_number" maxlength="100" value="{{ old('receipt_number', $expense?->receipt_number) }}" placeholder="Opcional" class="{{ $field }}">
    </div>

    <div class="md:col-span-2">
        <label class="{{ $label }}">Detalle</label>
        <textarea name="description" rows="3" maxlength="1000" placeholder="Opcional" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('description', $expense?->description) }}</textarea>
    </div>
</div>
