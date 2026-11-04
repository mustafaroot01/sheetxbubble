<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'stage_subject')->withTimestamps();
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
