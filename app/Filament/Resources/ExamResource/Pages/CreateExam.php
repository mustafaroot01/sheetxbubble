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

        if (isset($data['model_answer']) && is_array($data['model_answer'])) {
            $data['model_answer'] = array_map(
                fn ($v) => ($v === '_clear' || $v === '') ? null : $v,
                $data['model_answer']
            );
        }

        return $data;
    }
}
