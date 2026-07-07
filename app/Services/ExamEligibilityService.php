<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentPayment;

class ExamEligibilityService
{
    public function canTakeExam(Student $student): bool
    {
        return ! $this->hasBlockingDebt($student);
    }

    public function hasBlockingDebt(Student $student): bool
    {
        return StudentPayment::query()
            ->where('student_id', $student->id)
            ->where('exam_blocked', true)
            ->whereNotIn('status', ['pagado', 'anulado'])
            ->exists();
    }
}
