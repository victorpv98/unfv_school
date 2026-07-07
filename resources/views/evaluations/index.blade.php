<x-layouts.app title="Calificar docentes">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Calificar docentes</h2>
        @if($period)
            <p class="text-slate-600">Periodo activo: {{ $period->name }} ({{ $period->starts_at->format('d/m/Y') }} - {{ $period->ends_at->format('d/m/Y') }})</p>
        @else
            <p class="text-red-700">No hay periodo de evaluación activo.</p>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($teachers as $teacher)
            <div class="school-card rounded-lg p-5">
                <h3 class="font-semibold text-[#123b7a]">{{ $teacher->first_names }} {{ $teacher->last_names }}</h3>
                <p class="text-sm text-slate-500">{{ $teacher->specialty }}</p>
                @if(in_array($teacher->id, $completed, true))
                    <span class="mt-4 inline-block rounded-md bg-green-100 px-3 py-1 text-sm text-green-700">Evaluado</span>
                @elseif($period)
                    <a class="school-button-primary mt-4 inline-block rounded-md px-4 py-2 text-sm font-semibold" href="{{ route('evaluations.create', $teacher) }}">Evaluar</a>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.app>
