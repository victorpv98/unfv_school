<x-layouts.app title="Nueva matrícula">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Nueva matrícula</h2>
        <p class="text-slate-600">Registra alumno, apoderado, matrícula y pagos en una sola operación.</p>
    </div>

    @if($summary)
        <div class="school-panel mb-6 rounded-lg border-l-4 border-green-600 p-5">
            <h3 class="font-semibold text-[#123b7a]">Resumen generado</h3>
            <div class="mt-3 grid gap-3 text-sm md:grid-cols-4">
                <div><span class="font-semibold">Alumno:</span> {{ $summary['student'] }}</div>
                <div><span class="font-semibold">Apoderado:</span> {{ $summary['guardian'] }}</div>
                <div><span class="font-semibold">Matrícula:</span> {{ $summary['academic'] }}</div>
                <div><span class="font-semibold">Pagos generados:</span> {{ $summary['payments_created'] }}</div>
            </div>
        </div>
    @endif

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

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ route('enrollments.wizard.store') }}">
        @csrf

        <section>
            <h3 class="text-lg font-semibold text-[#123b7a]">1. Alumno</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Usar alumno existente</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="student_id">
                        <option value="">Crear / buscar por DNI o código</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->code }} - {{ $student->first_names }} {{ $student->last_names }} - {{ $student->dni }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Código</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="student_code" value="{{ old('student_code') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Nombres</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="student_first_names" value="{{ old('student_first_names') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Apellidos</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="student_last_names" value="{{ old('student_last_names') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">DNI</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="student_dni" value="{{ old('student_dni') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Fecha de nacimiento</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="date" name="student_birth_date" value="{{ old('student_birth_date') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Género</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="student_gender">
                        <option value="">Seleccione</option>
                        <option value="Femenino" @selected(old('student_gender') === 'Femenino')>Femenino</option>
                        <option value="Masculino" @selected(old('student_gender') === 'Masculino')>Masculino</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Dirección</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="student_address" value="{{ old('student_address') }}">
                </div>
                <label class="md:col-span-2 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="create_student_user" value="1" @checked(old('create_student_user'))>
                    Crear usuario de acceso para el alumno
                </label>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Correo acceso alumno</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="email" name="student_user_email" value="{{ old('student_user_email') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Contraseña temporal alumno</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="password" name="student_user_password">
                </div>
            </div>
        </section>

        <section class="mt-8 border-t border-[#e7e5c9] pt-6">
            <h3 class="text-lg font-semibold text-[#123b7a]">2. Apoderado</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Usar apoderado existente</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="guardian_id">
                        <option value="">Crear / buscar por DNI o teléfono</option>
                        @foreach($guardians as $guardian)
                            <option value="{{ $guardian->id }}" @selected(old('guardian_id') == $guardian->id)>{{ $guardian->first_names }} {{ $guardian->last_names }} - {{ $guardian->dni }} - {{ $guardian->phone }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Parentesco</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="relationship" required>
                        <option value="Madre" @selected(old('relationship') === 'Madre')>Madre</option>
                        <option value="Padre" @selected(old('relationship') === 'Padre')>Padre</option>
                        <option value="Apoderado" @selected(old('relationship') === 'Apoderado')>Apoderado</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Nombres</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="guardian_first_names" value="{{ old('guardian_first_names') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Apellidos</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="guardian_last_names" value="{{ old('guardian_last_names') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">DNI</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="guardian_dni" value="{{ old('guardian_dni') }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Teléfono</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="guardian_phone" value="{{ old('guardian_phone') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Correo</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="email" name="guardian_email" value="{{ old('guardian_email') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Dirección</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" name="guardian_address" value="{{ old('guardian_address') }}">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', true))>
                    Apoderado principal
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="create_guardian_user" value="1" @checked(old('create_guardian_user'))>
                    Crear usuario de acceso para el apoderado
                </label>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Correo acceso apoderado</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="email" name="guardian_user_email" value="{{ old('guardian_user_email') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Contraseña temporal apoderado</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="password" name="guardian_user_password">
                </div>
            </div>
        </section>

        <section class="mt-8 border-t border-[#e7e5c9] pt-6">
            <h3 class="text-lg font-semibold text-[#123b7a]">3. Datos académicos y pagos</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Año académico</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="number" name="academic_year" value="{{ old('academic_year', now()->year) }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Nivel</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="level_id" required>
                        <option value="">Seleccione</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Grado</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="grade_id" required>
                        <option value="">Seleccione</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" @selected(old('grade_id') == $grade->id)>{{ $grade->level->name }} - {{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Sección</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="section_name" required>
                        <option value="A" @selected(old('section_name') === 'A')>A</option>
                        <option value="B" @selected(old('section_name') === 'B')>B</option>
                        <option value="C" @selected(old('section_name') === 'C')>C</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Fecha de matrícula</label>
                    <input class="mt-1 w-full rounded-md px-3 py-2" type="date" name="enrolled_at" value="{{ old('enrolled_at', now()->toDateString()) }}" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-[#123b7a]">Estado</label>
                    <select class="mt-1 w-full rounded-md px-3 py-2" name="enrollment_status" required>
                        <option value="matriculado" @selected(old('enrollment_status') === 'matriculado')>Matriculado</option>
                        <option value="pendiente" @selected(old('enrollment_status') === 'pendiente')>Pendiente</option>
                        <option value="observado" @selected(old('enrollment_status') === 'observado')>Observado</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-[#123b7a]">Observaciones</label>
                    <textarea class="mt-1 w-full rounded-md px-3 py-2" name="observations" rows="3">{{ old('observations') }}</textarea>
                </div>
            </div>
        </section>

        <div class="mt-6 flex gap-2">
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Crear matrícula y generar pagos</button>
            <a class="rounded-md bg-slate-100 px-4 py-2 hover:bg-slate-200" href="{{ route('resources.index', 'enrollments') }}">Ver matrículas</a>
        </div>
    </form>
</x-layouts.app>
