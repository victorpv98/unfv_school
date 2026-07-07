<x-layouts.app title="Dashboard">
    @php
        $priorityCards = collect($cards)->only([
            'Matrículas activas',
            'Pagos pendientes',
            'Pagos en mora',
            'Sin derecho a examen',
        ]);

        $peopleCards = collect($cards)->only([
            'Alumnos',
            'Apoderados',
            'Docentes',
            'Usuarios',
        ]);

        $activityCards = collect($cards)->only([
            'Comunicados publicados',
            'Comunicados por leer',
            'Evaluaciones',
        ]);

        $attentionItems = [
            ['label' => 'Pagos pendientes', 'value' => $cards['Pagos pendientes'] ?? 0, 'tone' => 'warning'],
            ['label' => 'Pagos en mora', 'value' => $cards['Pagos en mora'] ?? 0, 'tone' => 'danger'],
            ['label' => 'Sin derecho a examen', 'value' => $cards['Sin derecho a examen'] ?? 0, 'tone' => 'danger'],
            ['label' => 'Comunicados por leer', 'value' => $cards['Comunicados por leer'] ?? 0, 'tone' => 'info'],
        ];
    @endphp

    <div class="mb-4 flex flex-col justify-between gap-3 md:flex-row md:items-end">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wide text-[#73726c]">Panel principal</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#1a1a18]">Operación escolar</h2>
            <p class="mt-1 text-sm text-[#73726c]">Matrícula, pagos, comunicados y evaluación docente en una sola vista.</p>
        </div>
        <div class="inline-flex w-fit items-center gap-2 rounded-md border border-[#e2e1db] bg-white px-3 py-2 text-xs font-semibold text-[#3d3d3a]">
            <span class="h-2 w-2 rounded-full bg-[#078b2f]"></span>
            {{ auth()->user()->role_label }}
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_320px]">
        <section class="space-y-4">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach($priorityCards as $label => $value)
                    <div class="school-card rounded-lg p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#73726c]">{{ $label }}</p>
                            <span class="rounded-full bg-[#f7f7f5] px-2 py-1 text-[11px] font-bold text-[#73726c]">Hoy</span>
                        </div>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-[#17427f]">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="school-panel rounded-lg">
                <div class="flex flex-col justify-between gap-3 border-b border-[#e2e1db] px-5 py-4 md:flex-row md:items-center">
                    <div>
                        <h3 class="text-base font-semibold text-[#1a1a18]">Accesos frecuentes</h3>
                        <p class="mt-1 text-sm text-[#73726c]">Flujos principales para la gestión diaria.</p>
                    </div>
                </div>
                <div class="grid gap-0 divide-y divide-[#e2e1db] md:grid-cols-3 md:divide-x md:divide-y-0">
                    @if(auth()->user()->hasRole('administrador', 'secretaria'))
                        <a class="group p-5 transition hover:bg-[#f7f7f5]" href="{{ route('enrollments.wizard.create') }}">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-[#17427f]">Nueva matrícula</span>
                                <span class="rounded-md bg-[#eef4fd] px-2 py-1 text-xs font-bold text-[#17427f] group-hover:bg-[#dbeafe]">+</span>
                            </div>
                            <p class="mt-2 text-sm text-[#73726c]">Alumno, apoderado, matrícula y pagos.</p>
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('administrador', 'apoderado'))
                        <a class="group p-5 transition hover:bg-[#f7f7f5]" href="{{ route('payments.index') }}">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-[#17427f]">Pagos</span>
                                <span class="rounded-md bg-[#f7f7f5] px-2 py-1 text-xs font-bold text-[#73726c] group-hover:bg-[#f0efe9]">S/</span>
                            </div>
                            <p class="mt-2 text-sm text-[#73726c]">Consulta y registro de mensualidades.</p>
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('administrador', 'docente'))
                        <a class="group p-5 transition hover:bg-[#f7f7f5]" href="{{ route('reports.index') }}">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-[#17427f]">{{ auth()->user()->hasRole('docente') ? 'Mis evaluaciones' : 'Reportes' }}</span>
                                <span class="rounded-md bg-[#f7f7f5] px-2 py-1 text-xs font-bold text-[#73726c] group-hover:bg-[#f0efe9]">R</span>
                            </div>
                            <p class="mt-2 text-sm text-[#73726c]">Indicadores principales del colegio.</p>
                        </a>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="school-panel rounded-lg">
                    <div class="border-b border-[#e2e1db] px-5 py-4">
                        <h3 class="text-base font-semibold text-[#1a1a18]">Comunidad escolar</h3>
                        <p class="mt-1 text-sm text-[#73726c]">Volumen actual de usuarios y participantes.</p>
                    </div>
                    <div class="divide-y divide-[#e2e1db]">
                        @foreach($peopleCards as $label => $value)
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm font-medium text-[#3d3d3a]">{{ $label }}</span>
                                <span class="rounded-full bg-[#f7f7f5] px-2.5 py-1 text-sm font-bold text-[#17427f]">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="school-panel rounded-lg">
                    <div class="border-b border-[#e2e1db] px-5 py-4">
                        <h3 class="text-base font-semibold text-[#1a1a18]">Actividad académica</h3>
                        <p class="mt-1 text-sm text-[#73726c]">Comunicados y evaluaciones registradas.</p>
                    </div>
                    <div class="divide-y divide-[#e2e1db]">
                        @foreach($activityCards as $label => $value)
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm font-medium text-[#3d3d3a]">{{ $label }}</span>
                                <span class="rounded-full bg-[#f7f7f5] px-2.5 py-1 text-sm font-bold text-[#17427f]">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="school-panel rounded-lg">
                <div class="border-b border-[#e2e1db] px-5 py-4">
                    <h3 class="text-base font-semibold text-[#1a1a18]">Docentes mejor evaluados</h3>
                    <p class="mt-1 text-sm text-[#73726c]">Ranking por promedio de evaluación registrado.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#f7f7f5] text-[#73726c]">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Docente</th>
                                <th class="px-5 py-3 font-semibold">Especialidad</th>
                                <th class="px-5 py-3 text-right font-semibold">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bestTeachers as $teacher)
                                <tr class="border-t border-[#e2e1db] hover:bg-[#f7f7f5]">
                                    <td class="px-5 py-3 font-medium text-[#1a1a18]">{{ $teacher->first_names }} {{ $teacher->last_names }}</td>
                                    <td class="px-5 py-3 text-[#73726c]">{{ $teacher->specialty }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-[#17427f]">{{ number_format($teacher->evaluations_avg_average_score ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-4 text-[#73726c]">Aún no hay evaluaciones registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <section class="school-panel rounded-lg">
                <div class="border-b border-[#e2e1db] px-4 py-3">
                    <h3 class="text-sm font-semibold text-[#1a1a18]">Atención operativa</h3>
                    <p class="mt-1 text-xs text-[#73726c]">Elementos que requieren seguimiento.</p>
                </div>
                <div class="divide-y divide-[#e2e1db]">
                    @foreach($attentionItems as $item)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="text-sm font-medium text-[#3d3d3a]">{{ $item['label'] }}</span>
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-bold',
                                'bg-[#fef9c3] text-[#915503]' => $item['tone'] === 'warning',
                                'bg-[#fee2e2] text-[#b91c1c]' => $item['tone'] === 'danger',
                                'bg-[#eef4fd] text-[#17427f]' => $item['tone'] === 'info',
                            ])>{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            @if(auth()->user()->hasRole('docente') && $teacherStats)
                <section class="school-panel rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-[#1a1a18]">Mis evaluaciones</h3>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-md border border-[#e2e1db] bg-[#f7f7f5] p-3">
                            <p class="text-xs text-[#73726c]">Promedio</p>
                            <p class="mt-2 text-2xl font-bold text-[#17427f]">{{ number_format($teacherStats['average'], 2) }}</p>
                        </div>
                        <div class="rounded-md border border-[#e2e1db] bg-[#f7f7f5] p-3">
                            <p class="text-xs text-[#73726c]">Recibidas</p>
                            <p class="mt-2 text-2xl font-bold text-[#17427f]">{{ $teacherStats['count'] }}</p>
                        </div>
                    </div>
                    @if($teacherStats['comments']->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            @foreach($teacherStats['comments'] as $comment)
                                <p class="rounded-md border border-[#e2e1db] bg-white p-3 text-sm text-[#3d3d3a]">{{ $comment->comment }}</p>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            @if(auth()->user()->hasRole('apoderado') && $guardianStats)
                <section class="school-panel rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-[#1a1a18]">Resumen familiar</h3>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between rounded-md bg-[#f7f7f5] px-3 py-2 text-sm"><span>Hijos registrados</span><strong>{{ $guardianStats['children'] }}</strong></div>
                        <div class="flex justify-between rounded-md bg-[#f7f7f5] px-3 py-2 text-sm"><span>Pagos pendientes</span><strong>{{ $guardianStats['pending'] }}</strong></div>
                        <div class="flex justify-between rounded-md bg-[#f7f7f5] px-3 py-2 text-sm"><span>Pagos realizados</span><strong>{{ $guardianStats['paid'] }}</strong></div>
                    </div>
                </section>
            @endif

            @if(auth()->user()->hasRole('alumno') && $studentStats)
                <section class="school-panel rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-[#1a1a18]">Mi actividad</h3>
                    <div class="mt-4 rounded-md bg-[#f7f7f5] px-3 py-2 text-sm">
                        <span class="text-[#73726c]">Evaluaciones completadas</span>
                        <p class="mt-2 text-2xl font-bold text-[#17427f]">{{ $studentStats['completed'] }}</p>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
