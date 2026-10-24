{{--
    Barra de carga (arriba de la pantalla, estilo YouTube/GitHub): como el sistema recarga la página
    entera al navegar (no es una SPA), esto es lo que le avisa al usuario "ya se está moviendo" apenas
    hace clic en un link o envía un formulario, y se completa solita cuando la página nueva ya cargó.
--}}
<div id="page-loader-bar" class="fixed top-0 left-0 h-[3px] bg-emerald-500 z-[10000] pointer-events-none"
    style="width:0%; opacity:0;"></div>

<script>
    (function () {
        const bar = document.getElementById('page-loader-bar');
        if (!bar) return;

        let hideTimeout;

        // Arranca en 0 y se acerca a un 85% sin llegar del todo: el resto lo completa finish()
        // cuando la página de verdad terminó de cargar (puede tardar más o menos, no lo sabemos de antemano).
        function start() {
            clearTimeout(hideTimeout);
            bar.style.transition = 'none';
            bar.style.opacity = '1';
            bar.style.width = '0%';
            bar.offsetHeight; // fuerza el reflow para que la transición de abajo sí anime desde 0
            bar.style.transition = 'width 1s cubic-bezier(0.1, 0.7, 0.6, 1)';
            bar.style.width = '85%';
        }

        function finish() {
            clearTimeout(hideTimeout);
            bar.style.transition = 'width 200ms ease-out';
            bar.style.width = '100%';
            hideTimeout = setTimeout(() => {
                bar.style.transition = 'opacity 250ms ease-out';
                bar.style.opacity = '0';
                hideTimeout = setTimeout(() => {
                    bar.style.transition = 'none';
                    bar.style.width = '0%';
                }, 250);
            }, 150);
        }

        // La página (esta misma) ya cargó: si se mostró la barra antes de llegar acá, se completa.
        // Si viene del caché de "atrás/adelante" del navegador, no hubo carga real: no se muestra nada.
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                bar.style.transition = 'none';
                bar.style.opacity = '0';
                bar.style.width = '0%';
            } else {
                finish();
            }
        });

        function hasWireAttribute(el) {
            return el && [...el.attributes].some((a) => a.name.startsWith('wire:'));
        }

        // Clic en un link que va a navegar de verdad (no pestaña nueva, no ancla, no Livewire, no ya cancelado).
        document.addEventListener('click', (e) => {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            const link = e.target.closest('a');
            if (!link || !link.href) return;
            if (link.target === '_blank' || link.hasAttribute('download') || hasWireAttribute(link)) return;
            if (link.protocol === 'javascript:' || link.getAttribute('href')?.startsWith('#')) return;
            if (link.hostname !== window.location.hostname) return;

            start();
        });

        // Envío de un formulario normal (no Livewire): también navega de página.
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (e.defaultPrevented || !(form instanceof HTMLFormElement) || hasWireAttribute(form)) return;

            start();
        });
    })();
</script>
