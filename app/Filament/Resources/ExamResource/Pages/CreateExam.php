<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['teacher_id'] = auth()->id();

        if ($data['total_mark'] && $data['question_count']) {
            $data['mark_per_question'] = round($data['total_mark'] / $data['question_count'], 2);
        }

        return $data;
    }
}
