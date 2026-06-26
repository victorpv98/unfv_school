<x-layouts.app title="Dashboard">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Dashboard</h2>
        <p class="text-slate-600">Resumen principal del sistema escolar.</p>
    </div>

    <div class="mb-6 grid gap-3 md:grid-cols-3">
        @if(auth()->user()->hasRole('administrador', 'secretaria'))
            <a class="school-button-primary rounded-lg p-4 font-semibold" href="{{ route('enrollments.wizard.create') }}">
                Nueva matrícula
                <span class="mt-1 block text-sm font-normal text-white/80">Alumno, apoderado, matrícula y pagos.</span>
            </a>
        @endif
        @if(auth()->user()->hasRole('administrador', 'apoderado'))
            <a class="rounded-lg bg-white p-4 font-semibold text-[#123b7a] shadow-sm hover:bg-slate-50" href="{{ route('payments.index') }}">
                Pagos
                <span class="mt-1 block text-sm font-normal text-slate-500">Consulta y registro de mensualidades.</span>
            </a>
        @endif
        @if(auth()->user()->hasRole('administrador', 'director', 'profesor'))
            <a class="rounded-lg bg-white p-4 font-semibold text-[#123b7a] shadow-sm hover:bg-slate-50" href="{{ route('reports.index') }}">
                Reportes
                <span class="mt-1 block text-sm font-normal text-slate-500">Indicadores principales del colegio.</span>
            </a>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach($cards as $label => $value)
            <div class="school-card rounded-lg p-5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-[#e94a1a]">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="school-panel mt-8 rounded-lg p-5">
        <h3 class="text-lg font-semibold text-[#123b7a]">Profesores mejor evaluados</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="p-3">Profesor</th>
                        <th class="p-3">Especialidad</th>
                        <th class="p-3">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bestTeachers as $teacher)
                        <tr class="border-t border-slate-200 hover:bg-slate-50">
                            <td class="p-3">{{ $teacher->first_names }} {{ $teacher->last_names }}</td>
                            <td class="p-3">{{ $teacher->specialty }}</td>
                            <td class="p-3 font-semibold text-[#123b7a]">{{ number_format($teacher->evaluations_avg_average_score ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-3 text-slate-500">Aún no hay evaluaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
