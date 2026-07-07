<x-layouts.app title="Evaluar docente">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#123b7a]">Evaluar a {{ $teacher->first_names }} {{ $teacher->last_names }}</h2>
        <p class="text-slate-600">Tipo de evaluación: {{ ucfirst($type) }}. Escala: 1 muy bajo, 5 excelente.</p>
    </div>

    <form class="school-panel rounded-lg p-6" method="POST" action="{{ route('evaluations.store', $teacher) }}">
        @csrf
        <div class="space-y-4">
            @foreach($criteria as $criterion)
                <div class="rounded-md border border-slate-200 p-4">
                    <label class="font-medium text-[#123b7a]">{{ $criterion->name }}</label>
                    @if($criterion->description)
                        <p class="text-sm text-slate-500">{{ $criterion->description }}</p>
                    @endif
                    <select class="mt-2 w-full rounded-md px-3 py-2 md:w-48" name="scores[{{ $criterion->id }}]" required>
                        <option value="">Puntaje</option>
                        @for($score = 1; $score <= 5; $score++)
                            <option value="{{ $score }}">{{ $score }}</option>
                        @endfor
                    </select>
                </div>
            @endforeach
            <div>
                <label class="font-medium text-[#123b7a]">Comentario u observación</label>
                <textarea class="mt-1 w-full rounded-md px-3 py-2" name="comment" rows="4"></textarea>
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Guardar evaluación</button>
            <a class="rounded-md bg-slate-100 px-4 py-2 hover:bg-slate-200" href="{{ route('evaluations.index') }}">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
