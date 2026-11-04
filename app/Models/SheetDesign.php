<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SheetDesign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'title',
        'institution_name',
        'logo_path',
        'header_bg_color',
        'header_text_color',
        'accent_color',
        'border_color',
        'row_alt_color',
        'instructions',
        'show_instructions',
        'bubble_style',
        'auto_columns_threshold',
        'show_student_name',
        'show_student_id',
        'show_stage',
        'show_study_type',
        'show_subject',
        'show_date',
        'show_footer',
        'footer_text',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_instructions' => 'boolean',
            'show_student_name' => 'boolean',
            'show_student_id' => 'boolean',
            'show_stage' => 'boolean',
            'show_study_type' => 'boolean',
            'show_subject' => 'boolean',
            'show_date' => 'boolean',
            'show_footer' => 'boolean',
        ];
    }

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function toDesignConfig(): array
    {
        return [
            'title' => $this->title,
            'institution_name' => $this->institution_name,
            'logo_path' => $this->logo_path ? storage_path('app/public/' . $this->logo_path) : null,
            'header_bg_color' => $this->header_bg_color,
            'header_text_color' => $this->header_text_color,
            'accent_color' => $this->accent_color,
            'border_color' => $this->border_color,
            'row_alt_color' => $this->row_alt_color,
            'instructions' => $this->instructions,
            'show_instructions' => $this->show_instructions,
            'bubble_style' => $this->bubble_style,
            'auto_columns_threshold' => $this->auto_columns_threshold,
            'show_student_name' => $this->show_student_name,
            'show_student_id' => $this->show_student_id,
            'show_stage' => $this->show_stage,
            'show_study_type' => $this->show_study_type,
            'show_subject' => $this->show_subject,
            'show_date' => $this->show_date,
            'show_footer' => $this->show_footer,
            'footer_text' => $this->footer_text,
        ];
    }
}
