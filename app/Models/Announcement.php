<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function grade(): BelongsTo { return $this->belongsTo(Grade::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function payment(): BelongsTo { return $this->belongsTo(StudentPayment::class, 'student_payment_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function recipients(): HasMany { return $this->hasMany(AnnouncementRecipient::class); }
}
