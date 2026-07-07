<x-layouts.app title="Configuración de moras">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">Configuración de moras</h2>
            <p class="text-slate-600">Define tolerancia, porcentaje de mora y bloqueo de examen.</p>
        </div>
        <a class="school-button-primary rounded-md px-4 py-2 font-semibold" href="{{ route('late-fee-settings.create') }}">Nueva configuración</a>
    </div>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3">Nombre</th>
                    <th class="p-3">Año</th>
                    <th class="p-3">Tolerancia</th>
                    <th class="p-3">Mora</th>
                    <th class="p-3">Bloquea examen</th>
                    <th class="p-3">Estado</th>
                    <th class="p-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="p-3">{{ $setting->name }}</td>
                        <td class="p-3">{{ $setting->academicYear?->year ?? 'General' }}</td>
                        <td class="p-3">{{ $setting->grace_days }} días</td>
                        <td class="p-3">{{ number_format((float) $setting->late_fee_percentage, 2) }}%</td>
                        <td class="p-3">{{ $setting->blocks_exam_right ? 'Sí' : 'No' }}</td>
                        <td class="p-3">{{ ucfirst($setting->status) }}</td>
                        <td class="p-3 text-right">
                            <a class="rounded-md bg-slate-100 px-3 py-1 hover:bg-slate-200" href="{{ route('late-fee-settings.edit', $setting) }}">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-slate-500">No hay configuraciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $settings->links() }}</div>
</x-layouts.app>
