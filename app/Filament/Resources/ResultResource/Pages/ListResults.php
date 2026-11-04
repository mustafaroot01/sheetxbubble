<?php

namespace App\Filament\Resources\ResultResource\Pages;

use App\Exports\ExamResultsExport;
use App\Filament\Resources\ResultResource;
use App\Models\Exam;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListResults extends ListRecords
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label(__('app.result.export_excel'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('exam_id')
                        ->label(__('app.result.select_exam'))
                        ->options(function () {
                            $query = Exam::with(['subject', 'stage']);
                            $user = auth()->user();
                            if ($user->hasRole('teacher')) {
                                $query->where('teacher_id', $user->id);
                            }
                            return $query->get()->mapWithKeys(fn ($exam) =>
                                [$exam->id => $exam->subject->name . ' - ' . $exam->stage->name . ' (' . $exam->exam_date->format('Y-m-d') . ')']
                            );
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    $exam = Exam::with(['subject', 'stage'])->findOrFail($data['exam_id']);
                    $filename = 'results_' . $exam->subject->name . '_' . $exam->stage->name . '_' . now()->format('Y-m-d') . '.xlsx';

                    return Excel::download(new ExamResultsExport($exam), $filename);
                }),
        ];
    }
}
