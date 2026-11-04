<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sheet_designs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(false);

            // Header
            $table->string('title')->default('ورقة إجابة - Bubble Sheet');
            $table->string('institution_name')->nullable();
            $table->string('logo_path')->nullable();

            // Colors
            $table->string('header_bg_color')->default('#1a1a2e');
            $table->string('header_text_color')->default('#ffffff');
            $table->string('accent_color')->default('#0f3460');
            $table->string('border_color')->default('#333355');
            $table->string('row_alt_color')->default('#f8f8fc');

            // Instructions
            $table->string('instructions', 500)->default('ظلل الدائرة بالكامل بقلم رصاص. لا تستخدم حبر. لتصحيح إجابة، امسح بالممحاة جيداً.');
            $table->boolean('show_instructions')->default(true);

            // Layout
            $table->string('bubble_style')->default('circle');
            $table->integer('auto_columns_threshold')->default(25);
            $table->boolean('show_student_name')->default(true);
            $table->boolean('show_student_id')->default(true);
            $table->boolean('show_stage')->default(true);
            $table->boolean('show_study_type')->default(true);
            $table->boolean('show_subject')->default(true);
            $table->boolean('show_date')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->string('footer_text')->default('نظام تصحيح البابل شيت - Bubble Sheet System');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_designs');
    }
};
