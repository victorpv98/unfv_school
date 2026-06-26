<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationDetail extends Model
{
    protected $guarded = [];

    public function evaluation(): BelongsTo { return $this->belongsTo(TeacherEvaluation::class, 'teacher_evaluation_id'); }
    public function criterion(): BelongsTo { return $this->belongsTo(EvaluationCriterion::class, 'evaluation_criterion_id'); }
}
