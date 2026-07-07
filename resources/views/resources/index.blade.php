<x-layouts.app :title="$config['label']">
    <div class="mb-5 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#73726c]">Módulo administrativo</p>
            <h2 class="mt-1 text-2xl font-bold text-[#17427f]">{{ $config['label'] }}</h2>
            <p class="mt-1 text-sm text-[#73726c]">Gestión de registros del módulo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($resource === 'teachers' && auth()->user()->hasRole('administrador', 'secretaria'))
                <a class="school-button-secondary rounded-md px-4 py-2 font-semibold" href="{{ route('teacher-assignments.index') }}">Asignar cursos</a>
            @endif
            <a class="school-button-primary rounded-md px-4 py-2 font-semibold" href="{{ route('resources.create', $resource) }}">Nuevo</a>
        </div>
    </div>

    <form class="school-panel mb-4 flex gap-2 rounded-lg p-3">
        <input class="w-full rounded-md px-3 py-2 text-sm" name="q" value="{{ request('q') }}" placeholder="Buscar...">
        <button class="school-button-secondary rounded-md px-4 py-2 text-sm font-semibold">Buscar</button>
    </form>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#f7f7f5] text-[#73726c]">
                <tr>
                    @foreach($config['columns'] as $column)
                        <th class="px-4 py-3 font-semibold">{{ $config['column_labels'][$column] ?? $config['fields'][$column]['label'] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-t border-[#e2e1db] hover:bg-[#f7f7f5]">
                        @foreach($config['columns'] as $column)
                            <td class="px-4 py-3 text-[#3d3d3a]">{{ isset($config['column_values'][$column]) ? $config['column_values'][$column]($item) : $item->{$column} }}</td>
                        @endforeach
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a class="rounded-md border border-[#e2e1db] bg-white px-3 py-1 text-[#17427f] hover:bg-[#f0efe9]" href="{{ route('resources.edit', [$resource, $item->id]) }}">Editar</a>
                                <form method="POST" action="{{ route('resources.destroy', [$resource, $item->id]) }}" onsubmit="return confirm('¿Eliminar este registro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-md border border-red-200 bg-red-50 px-3 py-1 text-red-700 hover:bg-red-100">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($config['columns']) + 1 }}" class="px-4 py-5 text-[#73726c]">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>
