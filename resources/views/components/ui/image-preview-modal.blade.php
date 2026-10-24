{{-- Modal Reutilizable de Vista Previa de Imagen en Pantalla Completa / Lightbox --}}
@once
<div id="image-preview-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-[100] bg-slate-900/80 backdrop-blur-sm p-4 flex items-center justify-center transition-opacity"
    onclick="if(event.target === this) closeImagePreview()">
    <div class="relative max-w-3xl w-full max-h-[90vh] bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
            <h3 id="image-preview-title" class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-md">Vista Previa</h3>
            <button type="button" onclick="closeImagePreview()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4 flex items-center justify-center bg-gray-50 dark:bg-slate-900/80 overflow-auto max-h-[80vh]">
            <img id="image-preview-img" src="" alt="" class="max-h-[72vh] w-auto max-w-full object-contain rounded-lg shadow-lg">
        </div>
    </div>
</div>

<script>
    if (!window.openImagePreview) {
        window.openImagePreview = function(src, title) {
            const modal = document.getElementById('image-preview-modal');
            const img = document.getElementById('image-preview-img');
            const titleEl = document.getElementById('image-preview-title');
            if (!modal || !img) return;
            img.src = src;
            img.alt = title || 'Imagen';
            if (titleEl) titleEl.textContent = title || 'Vista Previa';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeImagePreview = function() {
            const modal = document.getElementById('image-preview-modal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('image-preview-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    window.closeImagePreview();
                }
            }
        });
    }
</script>
@endonce
