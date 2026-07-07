<?php

namespace App\Services;

use App\Models\LateFeeSetting;
use App\Models\StudentPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentLateFeeService
{
    public function __construct(private readonly AnnouncementService $announcementService) {}

    public function applyAll(?Carbon $today = null): array
    {
        $today ??= now();
        $reviewed = 0;
        $applied = 0;
        $notices = 0;
        $errors = [];

        StudentPayment::query()
            ->with(['student.guardians', 'paymentConcept.academicYear', 'enrollment'])
            ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
            ->whereNotNull('due_date')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($today, &$reviewed, &$applied, &$notices, &$errors) {
                foreach ($payments as $payment) {
                    $reviewed++;

                    try {
                        $result = $this->applyToPayment($payment, $today);
                        $applied += $result['applied'] ? 1 : 0;
                        $notices += $result['notice_generated'] ? 1 : 0;
                    } catch (\Throwable $exception) {
                        $errors[] = "Pago {$payment->id}: {$exception->getMessage()}";
                    }
                }
            });

        return compact('reviewed', 'applied', 'notices', 'errors');
    }

    public function applyToPayment(StudentPayment $payment, ?Carbon $today = null): array
    {
        $today ??= now();

        if (in_array($payment->status, ['pagado', 'anulado'], true) || ! $payment->due_date || $payment->late_fee_applied_at) {
            return ['applied' => false, 'notice_generated' => false];
        }

        $setting = $this->settingFor($payment);

        if (! $setting) {
            return ['applied' => false, 'notice_generated' => false];
        }

        $lateStartsAt = $payment->due_date->copy()->addDays($setting->grace_days + 1)->startOfDay();

        if ($today->copy()->startOfDay()->lt($lateStartsAt)) {
            return ['applied' => false, 'notice_generated' => false];
        }

        return DB::transaction(function () use ($payment, $setting) {
            $payment->refresh();

            if ($payment->late_fee_applied_at || in_array($payment->status, ['pagado', 'anulado'], true)) {
                return ['applied' => false, 'notice_generated' => false];
            }

            $originalAmount = (float) ($payment->original_amount ?: $payment->amount);
            $lateFeeAmount = round($originalAmount * ((float) $setting->late_fee_percentage / 100), 2);
            $totalAmount = round($originalAmount + $lateFeeAmount, 2);

            $payment->update([
                'original_amount' => $originalAmount,
                'late_fee_amount' => $lateFeeAmount,
                'total_amount' => $totalAmount,
                'status' => 'vencido',
                'late_fee_applied_at' => now(),
                'exam_blocked' => $setting->blocks_exam_right,
                'exam_blocked_at' => $setting->blocks_exam_right ? now() : null,
            ]);

            $noticeGenerated = false;

            if ($setting->auto_generate_notice && ! $payment->notice_generated_at) {
                $this->generateLateFeeNotice($payment->fresh(['student.guardians', 'paymentConcept']), $setting);
                $payment->update(['notice_generated_at' => now()]);
                $noticeGenerated = true;
            }

            return ['applied' => true, 'notice_generated' => $noticeGenerated];
        });
    }

    public function reconcilePayment(StudentPayment $payment): void
    {
        $total = (float) ($payment->total_amount ?: $payment->amount);

        if ((float) $payment->amount_paid >= $total && $payment->exam_blocked) {
            $payment->forceFill([
                'exam_blocked' => false,
                'exam_unblocked_at' => now(),
            ])->save();
        }
    }

    private function settingFor(StudentPayment $payment): ?LateFeeSetting
    {
        $yearId = $payment->paymentConcept?->academic_year_id ?? $payment->enrollment?->academic_year_id;

        return LateFeeSetting::query()
            ->where('status', 'activo')
            ->where(function ($query) use ($yearId) {
                $query->where('academic_year_id', $yearId)->orWhereNull('academic_year_id');
            })
            ->orderByRaw('case when academic_year_id is null then 1 else 0 end')
            ->latest('id')
            ->first();
    }

    private function generateLateFeeNotice(StudentPayment $payment, LateFeeSetting $setting): void
    {
        $student = $payment->student;
        $guardian = $student?->guardians()->wherePivot('is_primary', true)->first() ?? $student?->guardians()->first();
        $message = $this->replaceTokens($setting->notice_message ?: $this->defaultNoticeMessage(), $payment, $setting, $guardian);

        $this->announcementService->create([
            'title' => $setting->notice_title,
            'message' => $message,
            'type' => 'mora',
            'priority' => 'alta',
            'target_type' => $guardian ? 'guardian' : 'student',
            'student_id' => $student?->id,
            'guardian_id' => $guardian?->id,
            'student_payment_id' => $payment->id,
            'status' => 'published',
            'publish_at' => now(),
        ], null, true);
    }

    private function replaceTokens(string $message, StudentPayment $payment, LateFeeSetting $setting, $guardian): string
    {
        $student = $payment->student;

        return strtr($message, [
            '[NOMBRE_ALUMNO]' => trim(($student?->first_names ?? '').' '.($student?->last_names ?? '')),
            '[NOMBRE_APODERADO]' => trim(($guardian?->first_names ?? '').' '.($guardian?->last_names ?? '')),
            '[CONCEPTO]' => $payment->paymentConcept?->name ?? 'Mensualidad',
            '[MONTO_ORIGINAL]' => number_format((float) ($payment->original_amount ?: $payment->amount), 2),
            '[PORCENTAJE_MORA]' => number_format((float) $setting->late_fee_percentage, 2),
            '[MONTO_MORA]' => number_format((float) $payment->late_fee_amount, 2),
            '[TOTAL_A_PAGAR]' => number_format((float) $payment->total_amount, 2),
            '[FECHA_VENCIMIENTO]' => $payment->due_date?->format('d/m/Y') ?? '-',
            '[DIAS_TOLERANCIA]' => (string) $setting->grace_days,
        ]);
    }

    private function defaultNoticeMessage(): string
    {
        return 'Estimado apoderado, se informa que el alumno [NOMBRE_ALUMNO] mantiene una mensualidad vencida correspondiente a [CONCEPTO]. Se ha aplicado una comisión adicional del [PORCENTAJE_MORA]% sobre el monto establecido. Mientras no se regularice el pago, el alumno no tendrá derecho a rendir examen.';
    }
}
