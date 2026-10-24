@php
    $fields = [
        [
            'name' => 'name',
            'label' => 'Nombre',
            'type' => 'text',
            'value' => $usuario->name,
            'placeholder' => 'Ej. Juan Pérez',
            'required' => true,
        ],
        [
            'name' => 'email',
            'label' => 'Correo Electrónico',
            'type' => 'email',
            'value' => $usuario->email,
            'placeholder' => 'ejemplo@correo.com',
            'required' => true,
        ],
        [
            'name' => 'branche_id',
            'label' => 'Sucursal',
            'type' => 'select',
            'value' => $usuario->branche_id,
            'options' => $branches->pluck('name', 'id')->toArray(),
            'required' => true,
        ],
        [
            'name' => 'password',
            'label' => 'Cambiar Contraseña',
            'type' => 'password',
            'placeholder' => 'Deja en blanco para mantener la actual',
            'help' => 'Solo llena este campo si deseas cambiar la contraseña (mínimo 8 caracteres).',
        ],
        [
            'name' => 'roles',
            'label' => 'Roles Asignados',
            'type' => 'checkbox_group',
            'grid_cols' => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
            'value' => $userRoles ?? [],
            'options' => $roles->pluck('name', 'name')->toArray(),
            'required' => true,
        ],
    ];
@endphp

<x-ui.edit-page
    title="Editar Perfil: {{ $usuario->name }}"
    category="Edición de Usuario"
    :backRoute="route('usuarios.index')"
    :action="route('usuarios.update', $usuario->id)"
    method="PUT"
    :fields="$fields"
    submitText="Actualizar Usuario"
>
    @if ($isSuperAdmin)
        <div class="mt-6 p-4 bg-gray-50 dark:bg-slate-800/50 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Términos y Condiciones de Licencia
                </h4>

                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $usuario->hasAcceptedTerms() ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300' : ($usuario->is_designated_admin ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400') }}">
                    {{ $usuario->terms_status_label }}
                </span>
            </div>

            <label class="flex items-start sm:items-center gap-2.5 cursor-pointer">
                <input type="checkbox" name="is_designated_admin" value="1" @checked(old('is_designated_admin', $usuario->is_designated_admin))
                    class="mt-0.5 sm:mt-0 w-4 h-4 rounded border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                    Designar a este usuario como Administrador/a firmante de Términos y Condiciones
                </span>
            </label>

            <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                Al ingresar al sistema por primera vez, el usuario designado deberá leer y aceptar formalmente las condiciones de la licencia por suscripción para poder acceder a los módulos.
            </p>

            @if ($usuario->terms_ip || $usuario->terms_accepted_at)
                <div class="text-xs text-gray-600 dark:text-gray-400 pt-1">
                    @if ($usuario->terms_accepted_at)
                        <span>Aceptado el: <strong class="text-gray-800 dark:text-gray-200">{{ $usuario->terms_accepted_at->format('d/m/Y H:i') }}</strong></span>
                    @endif
                    @if ($usuario->terms_ip)
                        <span class="text-[11px] text-gray-400 dark:text-gray-500 ml-1">(IP: {{ $usuario->terms_ip }})</span>
                    @endif
                </div>
            @endif

            @if ($usuario->hasAcceptedTerms())
                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="reset_terms" value="1"
                            class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-amber-600 focus:ring-amber-500 cursor-pointer">
                        <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">
                            Reiniciar aceptación de términos (requerirá confirmación nuevamente en el próximo inicio de sesión)
                        </span>
                    </label>
                </div>
            @endif
        </div>
    @endif

    <div class="mt-5">
        <x-ui.alert type="warning" :dismissible="false" message="Guardar estos cambios forzará el cierre de sesión del usuario modificado si se cambiaron sus roles o clave." />
    </div>
</x-ui.edit-page>