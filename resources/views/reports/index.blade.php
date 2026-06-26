<x-layouts.app title="Reportes">
    <h2 class="mb-6 text-2xl font-bold text-[#123b7a]">Reportes básicos</h2>
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="school-card rounded-lg p-5">
            <h3 class="font-semibold text-[#123b7a]">Matrículas por estado</h3>
            @foreach($enrollmentsByStatus as $row)
                <p class="mt-3 flex justify-between border-b border-slate-100 pb-2"><span>{{ ucfirst($row->status) }}</span><strong class="text-[#e94a1a]">{{ $row->total }}</strong></p>
            @endforeach
        </section>
        <section class="school-card rounded-lg p-5">
            <h3 class="font-semibold text-[#123b7a]">Profesores por especialidad</h3>
            @foreach($teachersBySpecialty as $row)
                <p class="mt-3 flex justify-between border-b border-slate-100 pb-2"><span>{{ $row->specialty ?: 'Sin especialidad' }}</span><strong class="text-[#e94a1a]">{{ $row->total }}</strong></p>
            @endforeach
        </section>
        <section class="school-card rounded-lg p-5">
            <h3 class="font-semibold text-[#123b7a]">Promedio por profesor</h3>
            @foreach($evaluationAverages as $row)
                <p class="mt-3 flex justify-between border-b border-slate-100 pb-2"><span>{{ $row->teacher_name }}</span><strong class="text-[#e94a1a]">{{ number_format($row->average, 2) }}</strong></p>
            @endforeach
        </section>
    </div>
</x-layouts.app>
