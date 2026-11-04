<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('status', 20)->default('open')->after('model_answer');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
