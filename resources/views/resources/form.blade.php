<x-layouts.app :title="$config['label']">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">{{ $item ? 'Editar' : 'Nuevo' }}: {{ $config['label'] }}</h2>
        <p class="text-slate-600">Completa los datos solicitados.</p>
    </div>

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ $item ? route('resources.update', [$resource, $item->id]) : route('resources.store', $resource) }}">
        @csrf
        @if($item)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            @foreach($config['fields'] as $name => $field)
                @php($type = $field['type'] ?? 'text')
                @php($fieldValue = old($name, $item ? (isset($field['value']) ? $field['value']($item) : $item?->{$name}) : null))
                <div class="{{ in_array($type, ['textarea', 'multiselect'], true) ? 'md:col-span-2' : '' }}">
	                    <label class="text-sm font-medium text-[#123b7a]">{{ $field['label'] }}</label>
	                    @isset($field['help'])
	                        <p class="mt-1 text-xs text-slate-500">{{ $field['help'] }}</p>
	                    @endisset
	                    @if($type === 'select')
                        <select class="mt-1 w-full rounded-md px-3 py-2" name="{{ $name }}">
                            <option value="">Seleccione</option>
                            @foreach($options[$name] ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected($fieldValue == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'multiselect')
                        @php($selectedValues = collect(old($name, $item ? $item->{$field['relation']}->pluck('id')->all() : []))->map(fn($value) => (string) $value)->all())
                        <div class="mt-2 rounded-lg border border-[#e7e5c9] bg-white" data-multiselect data-max-selected="{{ $field['max_selected'] ?? '' }}">
                            <div class="border-b border-[#e7e5c9] bg-slate-50 px-4 py-3">
                                <p class="text-sm font-semibold text-[#123b7a]">{{ $field['label'] }}</p>
                                <p class="text-xs text-slate-500">
                                    Marca todos los registros que deseas asociar.
                                    @if(array_key_exists('is_primary', $field))
                                        El primero de la lista que esté marcado quedará como principal.
                                    @endif
                                    @isset($field['max_selected'])
                                        Máximo {{ $field['max_selected'] }}.
                                    @endisset
                                </p>
                                <input
                                    class="mt-3 w-full rounded-md px-3 py-2 text-sm"
                                    type="search"
                                    placeholder="Buscar..."
                                    data-multiselect-search
                                >
                                <p class="mt-2 text-xs text-slate-500" data-multiselect-summary></p>
                            </div>
                            <div class="max-h-72 space-y-2 overflow-y-auto p-4">
                                @forelse($options[$name] ?? [] as $value => $label)
                                    <label class="flex items-start gap-3 rounded-md border border-slate-200 bg-white p-3 hover:bg-slate-50" data-multiselect-option data-label="{{ Str::lower($label) }}">
                                        <input class="mt-1 rounded border-slate-300 text-[#123b7a]" type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked(in_array((string) $value, $selectedValues, true))>
                                        <span class="text-sm text-slate-800">{{ $label }}</span>
                                    </label>
                                @empty
                                    <p class="rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">No hay registros disponibles para asociar.</p>
                                @endforelse
                                <p class="hidden rounded-md bg-yellow-50 p-3 text-sm text-yellow-800" data-multiselect-empty>No se encontraron registros con esa búsqueda.</p>
                            </div>
                        </div>
                    @elseif($type === 'textarea')
                        <textarea class="mt-1 w-full rounded-md px-3 py-2" name="{{ $name }}" rows="3">{{ $fieldValue }}</textarea>
                    @elseif($type === 'boolean')
                        <label class="mt-2 flex items-center gap-2">
                            <input class="rounded border-slate-300 text-[#e94a1a]" type="checkbox" name="{{ $name }}" value="1" @checked($fieldValue ?? ($field['default'] ?? false))>
                            <span>Activo</span>
                        </label>
                    @else
                        <input class="mt-1 w-full rounded-md px-3 py-2" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $fieldValue }}" @isset($field['step']) step="{{ $field['step'] }}" @endisset>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex gap-2">
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Guardar</button>
            <a class="rounded-md bg-slate-100 px-4 py-2 hover:bg-slate-200" href="{{ route('resources.index', $resource) }}">Cancelar</a>
        </div>
    </form>

    <script>
        document.querySelectorAll('[data-multiselect]').forEach((container) => {
            const search = container.querySelector('[data-multiselect-search]');
            const summary = container.querySelector('[data-multiselect-summary]');
            const empty = container.querySelector('[data-multiselect-empty]');
            const options = Array.from(container.querySelectorAll('[data-multiselect-option]'));
            const checks = Array.from(container.querySelectorAll('input[type="checkbox"]'));
            const maxSelected = Number(container.dataset.maxSelected || 0);

            const refresh = () => {
                const term = (search?.value || '').trim().toLowerCase();
                let visible = 0;

                options.forEach((option) => {
                    const matches = option.dataset.label.includes(term);
                    option.classList.toggle('hidden', !matches);
                    if (matches) {
                        visible++;
                    }
                });

                if (empty) {
                    empty.classList.toggle('hidden', visible > 0);
                }

                const selected = checks.filter((check) => check.checked).length;
                if (summary) {
                    const maxText = maxSelected ? ` de ${maxSelected}` : '';
                    summary.textContent = `${selected}${maxText} seleccionados · ${visible} visibles`;
                }
            };

            checks.forEach((check) => {
                check.addEventListener('change', () => {
                    if (maxSelected && check.checked && checks.filter((item) => item.checked).length > maxSelected) {
                        check.checked = false;
                    }

                    refresh();
                });
            });

            search?.addEventListener('input', refresh);
            refresh();
        });
    </script>
</x-layouts.app>
