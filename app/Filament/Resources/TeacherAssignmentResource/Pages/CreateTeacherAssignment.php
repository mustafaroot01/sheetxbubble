<?php

namespace App\Filament\Resources\TeacherAssignmentResource\Pages;

use App\Filament\Resources\TeacherAssignmentResource;
use App\Models\TeacherAssignment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherAssignment extends CreateRecord
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $stageIds = $data['stage_ids'] ?? [$data['stage_id'] ?? null];
        unset($data['stage_ids']);

        $created = null;
        $count = 0;

        foreach ((array) $stageIds as $stageId) {
            $exists = TeacherAssignment::where('teacher_id', $data['teacher_id'])
                ->where('stage_id', $stageId)
                ->where('subject_id', $data['subject_id'])
                ->exists();

            if (!$exists) {
                $created = TeacherAssignment::create([
                    'teacher_id' => $data['teacher_id'],
                    'stage_id' => $stageId,
                    'subject_id' => $data['subject_id'],
                ]);
                $count++;
            }
        }

        if ($count > 1) {
            Notification::make()
                ->title(__('app.teacher_assignment.created_count', ['count' => $count]))
                ->success()
                ->send();
        }

        return $created ?? TeacherAssignment::create(array_merge($data, ['stage_id' => $stageIds[0] ?? null]));
    }
}
