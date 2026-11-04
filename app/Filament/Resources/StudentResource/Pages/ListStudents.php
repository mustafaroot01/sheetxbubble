<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Imports\StudentsImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label(__('app.student.import_excel'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label(__('app.student.import_file'))
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required(),
                    Forms\Components\Select::make('stage_id')
                        ->label(__('app.student.select_stage'))
                        ->options(\App\Models\Stage::pluck('name', 'id'))
                        ->required(),
                    Forms\Components\Select::make('study_type_id')
                        ->label(__('app.student.select_study_type'))
                        ->options(\App\Models\StudyType::pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = storage_path('app/public/' . $data['file']);

                    try {
                        Excel::import(
                            new StudentsImport($data['stage_id'], $data['study_type_id']),
                            $filePath
                        );

                        Notification::make()
                            ->title(__('app.student.import_excel') . ' ✓')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('app.correction.failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
