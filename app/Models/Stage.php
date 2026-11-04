<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'stage_subject')->withTimestamps();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
