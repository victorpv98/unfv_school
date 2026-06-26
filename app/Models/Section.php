<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $guarded = [];

    public function grade(): BelongsTo { return $this->belongsTo(Grade::class); }
}
