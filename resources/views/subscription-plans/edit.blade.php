<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <x-ui.page-header category="Suscripciones" title="Editar Plan"
            description="Actualiza los datos del plan de suscripción." />

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
            <form action="{{ route('subscription-plans.update', $plan) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    <x-ui.form-field type="text" name="name" label="Nombre del Plan" :value="$plan->name" required />

                    <x-ui.form-field type="select" name="billing_cycle" label="Ciclo de Facturación"
                        :value="$plan->billing_cycle"
                        :options="['monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual']" required />

                    <x-ui.form-field type="number" name="price" label="Precio" :value="$plan->price" required />
                    <x-ui.form-field type="text" name="currency" label="Moneda" :value="$plan->currency" />
                    <x-ui.form-field type="number" name="max_users" label="Máx. Usuarios" :value="$plan->max_users"
                        help="Deja vacío para ilimitado" />

                    <div class="flex items-center gap-2 mt-6">
                        <x-ui.form-field type="checkbox" name="active" label="" :value="$plan->active" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">Plan activo</span>
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.form-field type="textarea" name="description" label="Descripción" :value="$plan->description" :rows="2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.form-field type="textarea" name="features" label="Características"
                            :value="implode(chr(10), $plan->features ?? [])"
                            help="Una característica por línea." :rows="4" />
                    </div>

                    <div class="md:col-span-2">
                        @if ($plan->qr_image)
                            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">QR Actual</span>
                            <img src="{{ $plan->qr_image_url }}" alt="QR actual"
                                class="w-32 h-32 object-contain rounded-lg border border-gray-200 dark:border-slate-700 bg-white p-2 mb-4">
                        @endif

                        <x-ui.form-field type="file" name="qr_image" label="{{ $plan->qr_image ? 'Reemplazar QR de Pago' : 'Subir QR de Pago' }}"
                            help="{{ $plan->qr_image ? 'Deja vacío para conservar el QR actual.' : 'QR que verá el usuario al elegir pago por QR para este plan.' }}" />
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-end gap-2">
                    <a href="{{ route('subscription-plans.index') }}">
                        <x-ui.button type="button" variant="outline">Cancelar</x-ui.button>
                    </a>
                    <x-ui.button type="submit" variant="primary">Guardar Cambios</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>