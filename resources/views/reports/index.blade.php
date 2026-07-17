<x-layouts.app title="Reportes">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">Reportes</h2>
            <p class="text-slate-600">Tablas filtrables para gestión y exportación.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="school-button-secondary rounded-md px-4 py-2 text-sm font-semibold" href="{{ route('reports.export', ['format' => 'excel'] + request()->query()) }}">Excel</a>
            <a class="school-button-primary rounded-md px-4 py-2 text-sm font-semibold" href="{{ route('reports.export', ['format' => 'pdf'] + request()->query()) }}">PDF</a>
        </div>
    </div>

    <form class="school-panel rounded-lg p-5" method="GET" action="{{ route('reports.index') }}">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Reporte</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="report">
                    @foreach($reports as $key => $label)
                        <option value="{{ $key }}" @selected($report === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Buscar</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nombre, código o DNI">
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Estado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="status">
                    <option value="">Todos</option>
                    @foreach($options['statuses'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Año escolar</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="academic_year_id">
                    <option value="">Todos</option>
                    @foreach($options['years'] as $year)
                        <option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Nivel</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="level_id">
                    <option value="">Todos</option>
                    @foreach($options['levels'] as $level)
                        <option value="{{ $level->id }}" @selected(($filters['level_id'] ?? '') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Grado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="grade_id">
                    <option value="">Todos</option>
                    @foreach($options['grades'] as $grade)
                        <option value="{{ $grade->id }}" @selected(($filters['grade_id'] ?? '') == $grade->id)>{{ $grade->level->name }} - {{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Sección</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="section">
                    <option value="">Todas</option>
                    @foreach($options['sections'] as $section)
                        <option value="{{ $section }}" @selected(($filters['section'] ?? '') === $section)>{{ $section }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Docente</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="teacher_id">
                    <option value="">Todos</option>
                    @foreach($options['teachers'] as $teacher)
                        <option value="{{ $teacher->id }}" @selected(($filters['teacher_id'] ?? '') == $teacher->id)>{{ $teacher->first_names }} {{ $teacher->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Tipo evaluador</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="evaluator_type">
                    <option value="">Todos</option>
                    <option value="alumno" @selected(($filters['evaluator_type'] ?? '') === 'alumno')>Alumno</option>
                    <option value="apoderado" @selected(($filters['evaluator_type'] ?? '') === 'apoderado')>Apoderado</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Desde</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Hasta</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="flex items-end gap-2">
                <a class="school-button-secondary rounded-md px-4 py-2 text-sm font-semibold" href="{{ route('reports.index') }}">Limpiar</a>
                <button class="school-button-primary rounded-md px-4 py-2 text-sm font-semibold">Filtrar</button>
            </div>
        </div>
    </form>

    <section class="school-panel mt-6 overflow-hidden rounded-lg">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-200 px-4 py-3 md:flex-row md:items-center">
            <div>
                <h3 class="font-semibold text-[#123b7a]">{{ $title }}</h3>
                <p class="text-sm text-slate-500">{{ count($rows) }} registros mostrados.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        @foreach($headers as $header)
                            <th class="whitespace-nowrap p-3">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t border-slate-200 hover:bg-slate-50">
                            @foreach($row as $cell)
                                <td class="max-w-80 p-3 align-top">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" class="p-4 text-slate-500">No hay registros con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
