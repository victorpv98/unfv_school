<x-layouts.app title="Destinatarios">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Destinatarios</h2>
        <p class="text-slate-600">{{ $announcement->title }}</p>
    </div>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3">Usuario</th>
                    <th class="p-3">Alumno</th>
                    <th class="p-3">Apoderado</th>
                    <th class="p-3">Entregado</th>
                    <th class="p-3">Leído</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcement->recipients as $recipient)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="p-3">{{ $recipient->user?->name ?? '-' }}</td>
                        <td class="p-3">{{ $recipient->student ? $recipient->student->first_names.' '.$recipient->student->last_names : '-' }}</td>
                        <td class="p-3">{{ $recipient->guardian ? $recipient->guardian->first_names.' '.$recipient->guardian->last_names : '-' }}</td>
                        <td class="p-3">{{ $recipient->delivered_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="p-3">{{ $recipient->read_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4 text-slate-500">No hay destinatarios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
