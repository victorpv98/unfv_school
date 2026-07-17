<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['enrolled_at' => 'date'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function grade(): BelongsTo { return $this->belongsTo(Grade::class); }
}
