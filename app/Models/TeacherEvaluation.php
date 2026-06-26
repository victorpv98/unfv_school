<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherEvaluation extends Model
{
    protected $guarded = [];

    public function period(): BelongsTo { return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id'); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function details(): HasMany { return $this->hasMany(EvaluationDetail::class); }
}
