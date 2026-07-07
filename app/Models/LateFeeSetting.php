<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LateFeeSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'grace_days' => 'integer',
            'late_fee_percentage' => 'decimal:2',
            'blocks_exam_right' => 'boolean',
            'auto_generate_notice' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
