<?php

// Todas las Variantes Visuales
<x-ui.button variant="primary">Guardar Cambios</x-ui.button>

<x-ui.button variant="secondary">Cancelar</x-ui.button>

<x-ui.button variant="danger">Eliminar Cuenta</x-ui.button>

<x-ui.button variant="warning">Pausar Suscripción</x-ui.button>

<x-ui.button variant="success">Aprobar Solicitud</x-ui.button>

<x-ui.button variant="outline">Volver Atrás</x-ui.button>

// Control de Tamaños (size)
<x-ui.button size="sm">Filtrar</x-ui.button>

<x-ui.button size="md">Continuar</x-ui.button>

<x-ui.button size="lg">Comenzar Registro</x-ui.button>

// Integración de Íconos (icon e iconPosition)
<x-ui.button icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4'/>">
    Añadir Nuevo
</x-ui.button>

<x-ui.button
    iconPosition="right"
    icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'/>"
>
    Siguiente Paso
</x-ui.button>

// Modificadores de Estado y Estructura
<x-ui.button type="submit" id="btn-submit" :fullWidth="true">
    Iniciar Sesión
</x-ui.button>

<x-ui.button :disabled="true">
    Procesando Pago...
</x-ui.button>

// Inyección de Atributos Extra (Gracias a $attributes->merge)
<x-ui.button
    class="mt-4 uppercase tracking-wider"
    @click="open = false"
>
    Cerrar Modal
</x-ui.button>
