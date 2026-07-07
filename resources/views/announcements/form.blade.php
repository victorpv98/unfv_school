<x-layouts.app :title="$announcement ? 'Editar comunicado' : 'Nuevo comunicado'">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">{{ $announcement ? 'Editar' : 'Nuevo' }} comunicado</h2>
        <p class="text-slate-600">Selecciona el destinatario y el contenido del mensaje.</p>
    </div>

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ $announcement ? route('announcements.update', $announcement) : route('announcements.store') }}">
        @csrf
        @if($announcement)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-[#123b7a]">Título</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" name="title" value="{{ old('title', $announcement?->title) }}" required>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Tipo</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="type" required>
                    @foreach(['general' => 'General', 'academico' => 'Académico', 'pago' => 'Pago', 'mora' => 'Mora', 'examen' => 'Examen', 'urgente' => 'Urgente', 'otro' => 'Otro'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $announcement?->type ?? 'general') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Prioridad</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="priority" required>
                    @foreach(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $announcement?->priority ?? 'normal') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Destinatario</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="target_type" required>
                    @foreach([
                        'student' => 'Alumno específico',
                        'guardian' => 'Apoderado específico',
                        'teacher' => 'Docente específico',
                        'classroom' => 'Aula',
                        'level' => 'Nivel',
                        'grade' => 'Grado',
                        'all_students' => 'Todos los alumnos',
                        'all_guardians' => 'Todos los apoderados',
                        'all_users' => 'Todos los usuarios',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(old('target_type', $announcement?->target_type ?? 'all_students') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Estado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="status" required>
                    @foreach(['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado', 'cancelled' => 'Cancelado'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $announcement?->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Año escolar</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="academic_year_id">
                    <option value="">Seleccione</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $announcement?->academic_year_id) == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Nivel</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="level_id">
                    <option value="">Seleccione</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id', $announcement?->level_id) == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Grado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="grade_id">
                    <option value="">Seleccione</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" @selected(old('grade_id', $announcement?->grade_id) == $grade->id)>{{ $grade->level->name }} - {{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Sección</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="section">
                    <option value="">Seleccione</option>
                    @foreach(['A', 'B', 'C'] as $section)
                        <option value="{{ $section }}" @selected(old('section', $announcement?->section) === $section)>{{ $section }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Alumno</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="student_id">
                    <option value="">Seleccione</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id', $announcement?->student_id) == $student->id)>{{ $student->code }} - {{ $student->first_names }} {{ $student->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Apoderado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="guardian_id">
                    <option value="">Seleccione</option>
                    @foreach($guardians as $guardian)
                        <option value="{{ $guardian->id }}" @selected(old('guardian_id', $announcement?->guardian_id) == $guardian->id)>{{ $guardian->first_names }} {{ $guardian->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Docente</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="teacher_id">
                    <option value="">Seleccione</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $announcement?->teacher_id) == $teacher->id)>{{ $teacher->first_names }} {{ $teacher->last_names }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Publicar desde</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="datetime-local" name="publish_at" value="{{ old('publish_at', $announcement?->publish_at?->format('Y-m-d\\TH:i')) }}">
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Vence</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="datetime-local" name="expires_at" value="{{ old('expires_at', $announcement?->expires_at?->format('Y-m-d\\TH:i')) }}">
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-[#123b7a]">Mensaje</label>
                <textarea class="mt-1 w-full rounded-md px-3 py-2" name="message" rows="7" required>{{ old('message', $announcement?->message) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold" name="action" value="save">Guardar</button>
            <button class="school-button-secondary rounded-md px-4 py-2 font-semibold" name="action" value="publish">Guardar y publicar</button>
            <a class="rounded-md bg-slate-100 px-4 py-2 hover:bg-slate-200" href="{{ route('announcements.index') }}">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
