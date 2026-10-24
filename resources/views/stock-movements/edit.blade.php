@php
    $items = [
        ['label' => 'Tipo', 'value' => $stockMovement->type->name],
        ['label' => 'Almacén', 'value' => $stockMovement->warehouse->name],
        ['label' => 'Fecha del Movimiento', 'value' => $stockMovement->date->format('d/m/Y')],
        ['label' => 'Registrado', 'value' => $stockMovement->date_label],
        ['label' => 'Usuario', 'value' => $stockMovement->user->name],
    ];
@endphp

<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Inventario" title="Editar Movimiento de Stock"
            description="Solo se puede corregir la nota. El tipo, almacén, fecha y las líneas no son editables porque ya afectaron el stock." />

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5 md:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                @foreach ($items as $item)
                    <div class="p-3 bg-gray-50 dark:bg-slate-800/40 rounded-lg border border-gray-100 dark:border-gray-800">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">{{ $item['label'] }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['value'] }}</span>
                    </div>
                @endforeach
            </div>

            <form action="{{ route('stock-movements.update', $stockMovement) }}" method="POST">
                @csrf
                @method('PUT')

                <x-ui.form-field type="textarea" name="note" label="Nota" :value="old('note', $stockMovement->note)" :rows="3" />

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                    <a href="{{ route('stock-movements.index') }}">
                        <x-ui.button type="button" variant="outline">Cancelar</x-ui.button>
                    </a>
                    <x-ui.button type="submit" variant="primary">Guardar Cambios</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
