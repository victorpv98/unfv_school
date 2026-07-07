<x-layouts.app title="Comunicados">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">Comunicados</h2>
            <p class="text-slate-600">Mensajes institucionales, académicos, de pagos y avisos de mora.</p>
        </div>
        @if(auth()->user()->hasRole('administrador', 'secretaria', 'docente'))
            <a class="school-button-primary rounded-md px-4 py-2 font-semibold" href="{{ route('announcements.create') }}">Nuevo comunicado</a>
        @endif
    </div>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3">Título</th>
                    <th class="p-3">Tipo</th>
                    <th class="p-3">Prioridad</th>
                    <th class="p-3">Destino</th>
                    <th class="p-3">Estado</th>
                    <th class="p-3">Publicado</th>
                    <th class="p-3">Creado por</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="p-3">
                            <a class="font-semibold text-[#123b7a]" href="{{ route('announcements.show', $announcement) }}">{{ $announcement->title }}</a>
                        </td>
                        <td class="p-3">{{ ucfirst($announcement->type) }}</td>
                        <td class="p-3">{{ ucfirst($announcement->priority) }}</td>
                        <td class="p-3">{{ str_replace('_', ' ', $announcement->target_type) }}</td>
                        <td class="p-3">{{ ucfirst($announcement->status) }}</td>
                        <td class="p-3">{{ $announcement->publish_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="p-3">{{ $announcement->creator?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-slate-500">No hay comunicados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>
</x-layouts.app>
