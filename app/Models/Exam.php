<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Limit to exams a teacher may access: ones they created, plus any
     * whose (stage, subject) pair matches one of their assignments.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if (!$user->hasRole('teacher')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('exams.teacher_id', $user->id)
                ->orWhereExists(function ($sub) use ($user) {
                    $sub->selectRaw('1')
                        ->from('teacher_assignments as ta')
                        ->where('ta.teacher_id', $user->id)
                        ->whereColumn('ta.stage_id', 'exams.stage_id')
                        ->whereColumn('ta.subject_id', 'exams.subject_id');
                });
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if (!$user->hasRole('teacher')) {
            return true;
        }

        if ($this->teacher_id === $user->id) {
            return true;
        }

        return TeacherAssignment::where('teacher_id', $user->id)
            ->where('stage_id', $this->stage_id)
            ->where('subject_id', $this->subject_id)
            ->exists();
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
