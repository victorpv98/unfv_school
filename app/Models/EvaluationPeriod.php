<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationPeriod extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }
}
