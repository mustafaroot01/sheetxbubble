<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Exam extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'subject_id',
        'stage_id',
        'study_type_id',
        'teacher_id',
        'exam_date',
        'total_mark',
        'question_count',
        'mark_per_question',
        'custom_marks',
        'choices_count',
        'pass_mark',
        'model_answer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'model_answer' => 'array',
            'custom_marks' => 'array',
            'total_mark' => 'decimal:2',
            'mark_per_question' => 'decimal:2',
            'pass_mark' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject_id', 'stage_id', 'exam_date'])
            ->logOnlyDirty();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function studyType(): BelongsTo
    {
        return $this->belongsTo(StudyType::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function getMarkForQuestion(int $questionNumber): float
    {
        if ($this->custom_marks && isset($this->custom_marks[$questionNumber - 1])) {
            return (float) $this->custom_marks[$questionNumber - 1];
        }

        return (float) ($this->mark_per_question ?? ($this->total_mark / $this->question_count));
    }
}
