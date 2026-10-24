{{--
    Avisos generales de toda pantalla: lo que salió bien (success), lo que salió mal (error) y los errores de validación
    del formulario. Lo dibuja el layout, así ninguna pantalla se queda sin avisar. Si la pantalla ya mostró el mismo
    mensaje con su propia alerta, no se repite.
--}}
@php
    $shown = collect(request()->attributes->get('alerts_shown', []));
    $normalize = fn ($text) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text), ENT_QUOTES)));
    $alreadyShown = fn (string $type, string $message) => $shown->contains(fn ($a) => $a['type'] === $type && str_contains($a['text'], $normalize($message)));

    $flashes = [];
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $message = session($type);
        if (is_string($message) && trim($message) !== '' && ! $alreadyShown($type, $message)) {
            $flashes[] = [$type, $message];
        }
    }

    $validation = collect($errors->all())->reject(fn ($m) => $alreadyShown('error', $m))->values();
@endphp

@if ($flashes || $validation->isNotEmpty())
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 pt-6" role="alert">
        @foreach ($flashes as [$type, $message])
            <x-ui.alert :type="$type" :message="$message" />
        @endforeach

        @if ($validation->isNotEmpty())
            <x-ui.alert type="error">
                <span class="block font-semibold mb-1">Revisa los datos: no se pudo guardar.</span>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($validation as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif
    </div>
@endif
