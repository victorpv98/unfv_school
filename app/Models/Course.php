<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    protected $guarded = [];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function grade(): BelongsTo { return $this->belongsTo(Grade::class); }
    public function teachers(): BelongsToMany { return $this->belongsToMany(Teacher::class, 'teacher_assignments')->withPivot(['academic_year_id', 'level_id', 'grade_id', 'section'])->withTimestamps(); }
}
