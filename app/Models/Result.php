<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Result extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'student_id',
        'exam_id',
        'score',
        'total_mark',
        'correct_count',
        'wrong_count',
        'percentage',
        'status',
        'answer_details',
        'sheet_image_path',
    ];

    protected function casts(): array
    {
        return [
            'answer_details' => 'array',
            'score' => 'decimal:2',
            'total_mark' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['student_id', 'exam_id', 'score'])
            ->logOnlyDirty();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
