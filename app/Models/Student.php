<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Student extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'full_name',
        'student_id',
        'stage_id',
        'study_type_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'student_id'])
            ->logOnlyDirty();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function studyType(): BelongsTo
    {
        return $this->belongsTo(StudyType::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
}
