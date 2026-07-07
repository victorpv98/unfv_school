<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Services\PaymentLateFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LateFeeController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria'), 403);

        return view('late-fees.payments.index', [
            'payments' => StudentPayment::with(['student.guardians', 'paymentConcept'])
                ->where(function ($query) {
                    $query->where('late_fee_amount', '>', 0)->orWhere('exam_blocked', true)->orWhere('status', 'vencido');
                })
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function apply(PaymentLateFeeService $service): RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria'), 403);

        $result = $service->applyAll();

        return back()->with('status', "Moras procesadas. Revisados: {$result['reviewed']}. Aplicadas: {$result['applied']}. Comunicados: {$result['notices']}.");
    }
}
