<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->decimal('total_mark', 8, 2);
            $table->integer('correct_count');
            $table->integer('wrong_count');
            $table->decimal('percentage', 5, 2);
            $table->string('status');
            $table->json('answer_details');
            $table->string('sheet_image_path')->nullable();
            $table->unique(['student_id', 'exam_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
