<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['teacher_id', 'subject_id', 'stage_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
