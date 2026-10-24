<?php

// Los 4 Tipos de Alertas
<x-ui.alert type="success" message="¡El registro se ha guardado correctamente!" />

<x-ui.alert type="error" message="Hubo un problema al procesar la solicitud. Revisa los datos." />

<x-ui.alert type="warning" message="Tu sesión expirará pronto por inactividad." />

<x-ui.alert type="info" message="Recuerda que las notas cierran este fin de semana." />

// Formas de pasar el Contenido (Propiedad vs Slot)
<x-ui.alert type="success" :message="session('status')" />

<x-ui.alert type="info">
    El sistema se actualizará a las <strong>22:00 PM</strong>. Si quieres saber más, haz clic en <a href="/soporte" class="underline font-bold">este enlace</a>.
</x-ui.alert>

// Control de Cierre (dismissible)
<x-ui.alert type="warning" message="Mantenimiento programado en curso." :dismissible="false" />

// Integración real con Laravel (Caso de uso ideal)
@if (session('success'))
    <x-ui.alert type="success" :message="session('success')" />
@endif

@if (session('error'))
    <x-ui.alert type="error" :message="session('error')" />
@endif

{{-- O para mostrar errores globales de validación de un formulario --}}
@if ($errors->any())
    <x-ui.alert type="error">
        <ul class="list-disc pl-4 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
