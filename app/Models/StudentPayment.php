<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPayment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
            'late_fee_applied_at' => 'datetime',
            'exam_blocked' => 'boolean',
            'exam_blocked_at' => 'datetime',
            'exam_unblocked_at' => 'datetime',
            'notice_generated_at' => 'datetime',
            'paid_at' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function paymentConcept(): BelongsTo { return $this->belongsTo(PaymentConcept::class); }
    public function announcements(): HasMany { return $this->hasMany(Announcement::class); }
}
