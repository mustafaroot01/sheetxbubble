<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['total_mark'] && $data['question_count']) {
            $data['mark_per_question'] = round($data['total_mark'] / $data['question_count'], 2);
        }

        if (isset($data['model_answer']) && is_array($data['model_answer'])) {
            $data['model_answer'] = array_values(array_filter($data['model_answer'], fn ($v) => $v !== null && $v !== '' && $v !== '_clear'));
        }

        return $data;
    }
}
