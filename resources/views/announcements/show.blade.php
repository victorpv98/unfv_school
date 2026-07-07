<x-layouts.app :title="$announcement->title">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-start">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">{{ $announcement->title }}</h2>
            <p class="text-slate-600">{{ ucfirst($announcement->type) }} · {{ ucfirst($announcement->priority) }} · {{ ucfirst($announcement->status) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasRole('administrador', 'secretaria') || $announcement->created_by === auth()->id())
                <a class="rounded-md bg-slate-100 px-4 py-2 font-semibold hover:bg-slate-200" href="{{ route('announcements.edit', $announcement) }}">Editar</a>
                <a class="rounded-md bg-slate-100 px-4 py-2 font-semibold hover:bg-slate-200" href="{{ route('announcements.recipients', $announcement) }}">Destinatarios</a>
                @if($announcement->status !== 'published')
                    <form method="POST" action="{{ route('announcements.publish', $announcement) }}">@csrf<button class="school-button-primary rounded-md px-4 py-2 font-semibold">Publicar</button></form>
                @endif
                @if($announcement->status !== 'archived')
                    <form method="POST" action="{{ route('announcements.archive', $announcement) }}">@csrf<button class="rounded-md bg-slate-100 px-4 py-2 font-semibold hover:bg-slate-200">Archivar</button></form>
                @endif
            @endif
            @if($announcement->recipients->where('user_id', auth()->id())->whereNull('read_at')->isNotEmpty())
                <form method="POST" action="{{ route('announcements.read', $announcement) }}">@csrf<button class="school-button-secondary rounded-md px-4 py-2 font-semibold">Marcar leído</button></form>
            @endif
        </div>
    </div>

    <article class="school-panel rounded-lg p-6">
        <div class="prose max-w-none whitespace-pre-line text-slate-800">{{ $announcement->message }}</div>
        <div class="mt-6 grid gap-3 text-sm md:grid-cols-3">
            <div><span class="font-semibold">Destino:</span> {{ str_replace('_', ' ', $announcement->target_type) }}</div>
            <div><span class="font-semibold">Publicado:</span> {{ $announcement->publish_at?->format('d/m/Y H:i') ?? '-' }}</div>
            <div><span class="font-semibold">Creado por:</span> {{ $announcement->creator?->name ?? '-' }}</div>
        </div>
    </article>
</x-layouts.app>
