<x-layouts.app :title="$setting ? 'Editar mora' : 'Nueva mora'">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">{{ $setting ? 'Editar' : 'Nueva' }} configuración de mora</h2>
        <p class="text-slate-600">La mora por defecto del sistema es 5%, pero puede configurarse por año escolar.</p>
    </div>

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ $setting ? route('late-fee-settings.update', $setting) : route('late-fee-settings.store') }}">
        @csrf
        @if($setting)
            @method('PUT')
        @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Año escolar</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="academic_year_id">
                    <option value="">General</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $setting?->academic_year_id) == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Nombre</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" name="name" value="{{ old('name', $setting?->name) }}" required>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Días de tolerancia</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="number" min="0" name="grace_days" value="{{ old('grace_days', $setting?->grace_days ?? 5) }}" required>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Porcentaje de mora</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="number" step="0.01" min="0" name="late_fee_percentage" value="{{ old('late_fee_percentage', $setting?->late_fee_percentage ?? 5) }}" required>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="blocks_exam_right" value="1" @checked(old('blocks_exam_right', $setting?->blocks_exam_right ?? true))>
                Bloquea derecho a examen
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="auto_generate_notice" value="1" @checked(old('auto_generate_notice', $setting?->auto_generate_notice ?? true))>
                Genera comunicado automático
            </label>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Estado</label>
                <select class="mt-1 w-full rounded-md px-3 py-2" name="status" required>
                    <option value="activo" @selected(old('status', $setting?->status ?? 'activo') === 'activo')>Activo</option>
                    <option value="inactivo" @selected(old('status', $setting?->status) === 'inactivo')>Inactivo</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Título del comunicado</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" name="notice_title" value="{{ old('notice_title', $setting?->notice_title ?? 'Aviso de mora pendiente') }}" required>
            </div>
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-[#123b7a]">Mensaje base</label>
                <textarea class="mt-1 w-full rounded-md px-3 py-2" name="notice_message" rows="5">{{ old('notice_message', $setting?->notice_message) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex gap-2">
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Guardar</button>
            <a class="rounded-md bg-slate-100 px-4 py-2 hover:bg-slate-200" href="{{ route('late-fee-settings.index') }}">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
