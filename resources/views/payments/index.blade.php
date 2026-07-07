<x-layouts.app title="Pagos">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">Pagos</h2>
            <p class="text-slate-600">Mensualidades y matrícula asociadas a los alumnos.</p>
        </div>
        @if(auth()->user()->hasRole('administrador', 'secretaria'))
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->hasRole('administrador'))
                    <a class="rounded-md bg-slate-100 px-4 py-2 font-semibold hover:bg-slate-200" href="{{ route('resources.index', 'payment-concepts') }}">Configurar montos</a>
                @endif
                <a class="school-button-primary rounded-md px-4 py-2 font-semibold" href="{{ route('resources.index', 'student-payments') }}">Registrar pagos</a>
            </div>
        @endif
    </div>

    @if($students->isEmpty())
        <div class="school-panel rounded-lg p-5">
            <p class="text-slate-600">No hay alumnos asociados a este usuario.</p>
        </div>
    @elseif($concepts->isEmpty())
        <div class="school-panel rounded-lg p-5">
            <p class="text-slate-600">Aún no hay conceptos de pago activos configurados para las matrículas vigentes.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($students as $student)
                @php
                    $studentYearIds = $student->enrollments->pluck('academic_year_id')->filter()->unique();
                    $studentConcepts = $concepts->whereIn('academic_year_id', $studentYearIds);
                    $paymentsByConcept = $student->payments->keyBy('payment_concept_id');
                    $totalPending = $studentConcepts->sum(function ($concept) use ($paymentsByConcept) {
                        $payment = $paymentsByConcept->get($concept->id);
                        return $payment?->status === 'pagado' ? 0 : (float) ($payment?->amount ?? $concept->amount);
                    });
                @endphp

                <section class="school-panel rounded-lg p-5">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-[#123b7a]">{{ $student->first_names }} {{ $student->last_names }}</h3>
                            <p class="text-sm text-slate-500">Código: {{ $student->code }}</p>
                        </div>
                        <div class="rounded-md bg-yellow-50 px-4 py-2 text-sm font-semibold text-[#08285d]">
                            Pendiente: S/ {{ number_format($totalPending, 2) }}
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="p-3">Año</th>
                                    <th class="p-3">Concepto</th>
                                    <th class="p-3">Mes</th>
                                    <th class="p-3">Vencimiento</th>
                                    <th class="p-3">Monto</th>
                                    <th class="p-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentConcepts as $concept)
                                    @php
                                        $payment = $paymentsByConcept->get($concept->id);
                                        $dueDate = $payment?->due_date ?? $concept->due_date;
                                        $status = $payment?->status ?? ($dueDate && $dueDate->isPast() ? 'vencido' : 'pendiente');
                                        $statusClass = [
                                            'pagado' => 'bg-green-50 text-green-700',
                                            'vencido' => 'bg-red-50 text-red-700',
                                            'pendiente' => 'bg-yellow-50 text-yellow-700',
                                        ][$status] ?? 'bg-slate-50 text-slate-700';
                                        $monthName = $concept->month ? [
                                            3 => 'Marzo',
                                            4 => 'Abril',
                                            5 => 'Mayo',
                                            6 => 'Junio',
                                            7 => 'Julio',
                                            8 => 'Agosto',
                                            9 => 'Septiembre',
                                            10 => 'Octubre',
                                            11 => 'Noviembre',
                                            12 => 'Diciembre',
                                        ][$concept->month] : '-';
                                    @endphp
                                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                                        <td class="p-3">{{ $concept->academicYear?->year }}</td>
                                        <td class="p-3">{{ $concept->name }}</td>
                                        <td class="p-3">{{ $monthName }}</td>
                                        <td class="p-3">{{ $dueDate?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="p-3 font-semibold">S/ {{ number_format((float) ($payment?->amount ?? $concept->amount), 2) }}</td>
                                        <td class="p-3">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-3 text-slate-500">No hay conceptos configurados para este alumno.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.app>
