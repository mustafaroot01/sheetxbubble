<?php

namespace App\Exports;

use App\Models\Exam;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExamResultsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(protected Exam $exam) {}

    public function collection()
    {
        return $this->exam->results()->with('student')->orderBy('score', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Student ID',
            'Student Name',
            'Score',
            'Total Mark',
            'Percentage',
            'Correct',
            'Wrong',
            'Status',
        ];
    }

    public function map($result): array
    {
        static $counter = 0;
        $counter++;

        return [
            $counter,
            $result->student->student_id,
            $result->student->full_name,
            $result->score,
            $result->total_mark,
            $result->percentage . '%',
            $result->correct_count,
            $result->wrong_count,
            strtoupper($result->status),
        ];
    }

    public function title(): string
    {
        return $this->exam->subject->name . ' - ' . $this->exam->stage->name;
    }
}
