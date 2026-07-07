<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\PaymentConcept;
use App\Models\StudentPayment;

class PaymentGenerationService
{
    public function generateForEnrollment(Enrollment $enrollment): int
    {
        $created = 0;

        $concepts = PaymentConcept::query()
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('status', 'activo')
            ->orderByRaw("case when type = 'matricula' then 0 else 1 end")
            ->orderBy('month')
            ->get();

        foreach ($concepts as $concept) {
            $payment = StudentPayment::firstOrCreate([
                'student_id' => $enrollment->student_id,
                'payment_concept_id' => $concept->id,
            ], [
                'enrollment_id' => $enrollment->id,
                'amount' => $concept->amount,
                'original_amount' => $concept->amount,
                'late_fee_amount' => 0,
                'total_amount' => $concept->amount,
                'amount_paid' => 0,
                'status' => 'pendiente',
                'due_date' => $concept->due_date,
            ]);

            if ($payment->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    public function regeneratePendingForEnrollment(Enrollment $enrollment): int
    {
        StudentPayment::query()
            ->where('student_id', $enrollment->student_id)
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('status', ['pendiente', 'vencido'])
            ->update([
                'status' => 'anulado',
                'cancelled_at' => now(),
                'cancelled_reason' => 'Regenerado por cambio de matrícula.',
            ]);

        return $this->generateForEnrollment($enrollment);
    }
}
