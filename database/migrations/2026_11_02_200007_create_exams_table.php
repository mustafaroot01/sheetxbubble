<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('exam_date');
            $table->decimal('total_mark', 8, 2);
            $table->integer('question_count');
            $table->decimal('mark_per_question', 8, 2)->nullable();
            $table->json('custom_marks')->nullable();
            $table->integer('choices_count')->default(4);
            $table->decimal('pass_mark', 5, 2)->default(50.00);
            $table->json('model_answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
