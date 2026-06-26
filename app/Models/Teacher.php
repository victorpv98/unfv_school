<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function courses(): BelongsToMany { return $this->belongsToMany(Course::class)->withPivot(['academic_year_id', 'grade_id', 'section_id'])->withTimestamps(); }
    public function evaluations(): HasMany { return $this->hasMany(TeacherEvaluation::class); }
}
