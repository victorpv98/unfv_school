<x-layouts.app title="Pagos en mora">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h2 class="text-2xl font-bold text-[#123b7a]">Pagos en mora</h2>
            <p class="text-slate-600">Mensualidades vencidas, mora aplicada y bloqueo de examen.</p>
        </div>
        <form method="POST" action="{{ route('late-fees.apply') }}">
            @csrf
            <button class="school-button-primary rounded-md px-4 py-2 font-semibold">Aplicar moras ahora</button>
        </form>
    </div>

    <div class="school-panel overflow-x-auto rounded-lg">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3">Alumno</th>
                    <th class="p-3">Concepto</th>
                    <th class="p-3">Vencimiento</th>
                    <th class="p-3">Original</th>
                    <th class="p-3">Mora</th>
                    <th class="p-3">Total</th>
                    <th class="p-3">Estado</th>
                    <th class="p-3">Examen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="border-t border-slate-200 hover:bg-slate-50">
                        <td class="p-3">{{ $payment->student?->first_names }} {{ $payment->student?->last_names }}</td>
                        <td class="p-3">{{ $payment->paymentConcept?->name }}</td>
                        <td class="p-3">{{ $payment->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="p-3">S/ {{ number_format((float) ($payment->original_amount ?: $payment->amount), 2) }}</td>
                        <td class="p-3">S/ {{ number_format((float) $payment->late_fee_amount, 2) }}</td>
                        <td class="p-3 font-semibold">S/ {{ number_format((float) ($payment->total_amount ?: $payment->amount), 2) }}</td>
                        <td class="p-3">{{ ucfirst($payment->status) }}</td>
                        <td class="p-3">{{ $payment->exam_blocked ? 'Bloqueado' : 'Habilitado' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-4 text-slate-500">No hay pagos en mora.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</x-layouts.app>
