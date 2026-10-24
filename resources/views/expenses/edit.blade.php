<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @include('sales.partials.alerts')

        <x-ui.page-header category="Gastos" title="Editar Gasto #{{ $expense->id }}"
            description="Si cambias el monto o la caja, el dinero anterior vuelve a su caja y se descuenta el nuevo." />

        <form action="{{ route('expenses.update', $expense) }}" method="POST"
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
            @csrf
            @method('PUT')
            @include('expenses._form')

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('expenses.show', $expense) }}" class="px-4 h-10 inline-flex items-center text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</a>
                <button type="submit" class="px-5 h-10 text-xs font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar cambios</button>
            </div>
        </form>

        @if ($canCreateCategory)
            @include('expenses._category-modal')
        @endif
    </div>
</x-app-layout>
