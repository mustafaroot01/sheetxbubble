<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function __construct(
        protected int $stageId,
        protected int $studyTypeId,
    ) {}

    public function model(array $row)
    {
        return new Student([
            'full_name' => $row['full_name'] ?? $row['name'] ?? $row['student_name'],
            'student_id' => $row['student_id'] ?? $row['id'] ?? $row['university_id'],
            'stage_id' => $this->stageId,
            'study_type_id' => $this->studyTypeId,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => 'nullable',
            'name' => 'nullable',
            'student_name' => 'nullable',
            'student_id' => 'nullable',
            'id' => 'nullable',
            'university_id' => 'nullable',
        ];
    }
}
