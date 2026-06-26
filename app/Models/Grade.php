<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $guarded = [];

    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function sections(): HasMany { return $this->hasMany(Section::class); }
}
