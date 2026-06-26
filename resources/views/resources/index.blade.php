<x-layouts.app :title="$config['label']">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">{{ $config['label'] }}</h2>
            <p class="text-slate-600">Gestión de registros del módulo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($resource === 'teachers' && auth()->user()->hasRole('administrador', 'secretaria'))
                <a class="rounded-md bg-slate-100 px-4 py-2 font-semibold hover:bg-slate-200" href="{{ route('teacher-assignments.index') }}">Asignar cursos</a>
            @endif
            <a class="school-button-primary rounded-md px-4 py-2 font-semibold" href="{{ route('resources.create', $resource) }}">Nuevo</a>
        </div>
    </div>

    <form class="mb-4 flex gap-2">
        <input class="w-full rounded-md px-3 py-2" name="q" value="{{ request('q') }}" placeholder="Buscar...">
        <button class="school-button-secondary rounded-md px-4 py-2">Buscar</button>
    </form>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    @foreach($config['columns'] as $column)
                        <th class="p-3">{{ $config['column_labels'][$column] ?? $config['fields'][$column]['label'] ?? ucfirst(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th class="p-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        @foreach($config['columns'] as $column)
                            <td class="p-3">{{ isset($config['column_values'][$column]) ? $config['column_values'][$column]($item) : $item->{$column} }}</td>
                        @endforeach
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <a class="rounded-md bg-slate-100 px-3 py-1 hover:bg-slate-200" href="{{ route('resources.edit', [$resource, $item->id]) }}">Editar</a>
                                <form method="POST" action="{{ route('resources.destroy', [$resource, $item->id]) }}" onsubmit="return confirm('¿Eliminar este registro?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-md bg-red-100 px-3 py-1 text-red-700 hover:bg-red-200">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($config['columns']) + 1 }}" class="p-4 text-slate-500">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</x-layouts.app>
