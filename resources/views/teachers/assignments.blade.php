<x-layouts.app title="Asignación docente">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Asignación docente</h2>
        <p class="text-slate-600">Asocia docentes con cursos, grados y secciones por año escolar.</p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Revise los campos marcados.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ route('teacher-assignments.store') }}">
        @csrf
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Año escolar</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="academic_year_id" required>
                    <option value="">Seleccione</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Docente</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="teacher_id" required>
                    <option value="">Seleccione</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->first_names }} {{ $teacher->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Curso</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="course_id" required>
                    <option value="">Seleccione</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->name }} - {{ $course->level?->name }} {{ $course->grade?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Grado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="grade_id" required>
                    <option value="">Seleccione</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}">{{ $grade->level->name }} - {{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Sección</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="section" required>
                    <option value="">Seleccione</option>
                    @foreach($sections as $section)
                        <option value="{{ $section }}">{{ $section }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Asignar</button>
            </div>
        </div>
    </form>

    <form class="school-panel mt-8 rounded-lg p-6" method="GET" action="{{ route('teacher-assignments.index') }}">
        <div class="mb-4 flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <div>
                <h3 class="text-lg font-semibold text-[#123b7a]">Filtros</h3>
                <p class="text-sm text-slate-500">Filtra las asignaciones registradas.</p>
            </div>
            <div class="flex gap-2">
                <a class="school-button-secondary rounded-md px-4 py-2 text-sm font-semibold" href="{{ route('teacher-assignments.index') }}">Limpiar</a>
                <button class="school-button-primary rounded-md px-4 py-2 text-sm font-semibold">Filtrar</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Año escolar</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="academic_year_id">
                    <option value="">Todos</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Docente</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="teacher_id">
                    <option value="">Todos</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(($filters['teacher_id'] ?? '') == $teacher->id)>{{ $teacher->first_names }} {{ $teacher->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Curso</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="course_id">
                    <option value="">Todos</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? '') == $course->id)>{{ $course->name }} - {{ $course->level?->name }} {{ $course->grade?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Grado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="grade_id">
                    <option value="">Todos</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" @selected(($filters['grade_id'] ?? '') == $grade->id)>{{ $grade->level->name }} - {{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Sección</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="section">
                    <option value="">Todas</option>
                    @foreach($sections as $section)
                        <option value="{{ $section }}" @selected(($filters['section'] ?? '') === $section)>{{ $section }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="school-panel mt-8 overflow-x-auto rounded-lg">
        <div class="border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
            {{ $assignments->count() }} asignaciones encontradas.
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3">Año</th>
                    <th class="p-3">Docente</th>
                    <th class="p-3">Curso</th>
                    <th class="p-3">Nivel</th>
                    <th class="p-3">Grado</th>
                    <th class="p-3">Sección</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="p-3">{{ $assignment->year }}</td>
                        <td class="p-3">{{ $assignment->teacher_name }}</td>
                        <td class="p-3">{{ $assignment->course_name }}</td>
                        <td class="p-3">{{ $assignment->level_name }}</td>
                        <td class="p-3">{{ $assignment->grade_name }}</td>
                        <td class="p-3">{{ $assignment->section }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-slate-500">No hay asignaciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
