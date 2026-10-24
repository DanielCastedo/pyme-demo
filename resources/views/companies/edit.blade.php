<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Configuración" title="Editar Empresa"
            description="Actualiza los datos de la empresa." />

        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <div class="bg-white dark:bg-slate-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <form action="{{ route('companies.update', $company) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    <x-ui.form-field type="text" name="name" label="Nombre Comercial" :value="$company->name" required />
                    <x-ui.form-field type="text" name="legal_name" label="Razón Social" :value="$company->legal_name" />
                    <x-ui.form-field type="text" name="tax_id" label="NIT" :value="$company->tax_id" />
                    <x-ui.form-field type="text" name="phone_country_code" label="Código de País (WhatsApp)" :value="$company->phone_country_code" placeholder="Ej. 591" />
                    <x-ui.form-field type="text" name="phone" label="Teléfono" :value="$company->phone" />
                    <x-ui.form-field type="email" name="email" label="Correo" :value="$company->email" />
                    <x-ui.form-field type="text" name="currency" label="Moneda" :value="$company->currency" />
                    <x-ui.form-field type="text" name="city" label="Ciudad" :value="$company->city" />
                    <x-ui.form-field type="text" name="country" label="País" :value="$company->country" />

                    <div class="md:col-span-2">
                        <x-ui.form-field type="text" name="address" label="Dirección" :value="$company->address" />
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <a href="{{ route('companies.index') }}">
                        <x-ui.button type="button" variant="outline">Cancelar</x-ui.button>
                    </a>
                    <x-ui.button type="submit" variant="primary">Guardar Cambios</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>