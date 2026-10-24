{{-- Modal de alta rápida de categoría. Va FUERA del <form> del gasto (los formularios no se pueden anidar). Requiere el select #category-id. --}}
@php
    $field = 'w-full h-10 px-3 text-sm rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500';
    $label = 'block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5';
@endphp

@if ($canCreateCategory)
    <div id="category-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm p-4 flex items-center justify-center">
        <div class="w-full max-w-sm bg-white dark:bg-slate-900 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Nueva categoría de gasto</h3>
                <button type="button" id="category-modal-close" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form id="category-form" class="p-5 space-y-3">
                <div id="category-form-errors" class="hidden p-2.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-xs text-red-700 dark:text-red-300"></div>
                <div>
                    <label class="{{ $label }}">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="255" placeholder="Ej. Servicios básicos" class="{{ $field }}">
                </div>
                <div class="pt-1 flex justify-end gap-2">
                    <button type="button" id="category-modal-cancel" class="px-4 h-10 text-xs font-semibold rounded-lg border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="submit" id="category-save" class="px-4 h-10 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Guardar y seleccionar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const $ = (id) => document.getElementById(id);
            const modal = $('category-modal'), form = $('category-form'), errBox = $('category-form-errors'), saveBtn = $('category-save'), sel = $('category-id');
            const close = () => modal.classList.add('hidden');
            $('new-category-btn').addEventListener('click', () => { form.reset(); errBox.classList.add('hidden'); modal.classList.remove('hidden'); form.elements.name.focus(); });
            $('category-modal-close').addEventListener('click', close);
            $('category-modal-cancel').addEventListener('click', close);
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                saveBtn.disabled = true; saveBtn.textContent = 'Guardando...'; errBox.classList.add('hidden');
                try {
                    const res = await fetch(@json(route('expense-categories.store')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value },
                        body: JSON.stringify(Object.fromEntries(new FormData(form))),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.status === 422) {
                        errBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<div>${m.replace(/</g, '&lt;')}</div>`).join('');
                        errBox.classList.remove('hidden');
                        return;
                    }
                    if (!res.ok) throw new Error(data.message || 'No se pudo guardar la categoría.');
                    sel.add(new Option(data.name, data.id, true, true));
                    sel.value = data.id;
                    close();
                } catch (err) {
                    errBox.textContent = err.message; errBox.classList.remove('hidden');
                } finally {
                    saveBtn.disabled = false; saveBtn.textContent = 'Guardar y seleccionar';
                }
            });
        })();
    </script>
@endif
