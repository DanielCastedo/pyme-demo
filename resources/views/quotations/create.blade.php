<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @if (isset($errors) && $errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <x-ui.page-header
            category="Ventas"
            title="Nueva Proforma"
            description="Elabora una cotización comercial para el cliente con cálculo de stock referencial y precios oficiales de catálogo."
        />

        <x-quotations.builder
            :action="route('quotations.store')"
            method="POST"
            :warehouse="$warehouse"
        />

    </div>
</x-app-layout>
