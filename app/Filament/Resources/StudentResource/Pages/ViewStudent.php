<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function getTitle(): string
    {
        return __('app.student.profile') . ': ' . $this->record->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('app.student.profile'))
                ->icon('heroicon-o-user')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('full_name')->label(__('app.student.full_name'))->weight('bold')->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('student_id')->label(__('app.student.student_id'))->copyable()->badge(),
                        TextEntry::make('stage.name')->label(__('app.student.stage')),
                        TextEntry::make('studyType.name')->label(__('app.student.study_type')),
                    ]),
                ]),

            Section::make(__('app.student.stats'))
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Grid::make(6)->schema([
                        TextEntry::make('exams_taken')
                            ->label(__('app.student.exams_taken'))
                            ->state(fn (Student $record) => $record->results()->count())
                            ->badge()->color('info'),
                        TextEntry::make('average')
                            ->label(__('app.student.average'))
                            ->state(fn (Student $record) => $record->results()->exists()
                                ? round($record->results()->avg('percentage'), 1) . '%'
                                : '-'),
                        TextEntry::make('highest')
                            ->label(__('app.student.highest'))
                            ->state(fn (Student $record) => $record->results()->exists()
                                ? round($record->results()->max('percentage'), 1) . '%'
                                : '-')
                            ->color('success'),
                        TextEntry::make('lowest')
                            ->label(__('app.student.lowest'))
                            ->state(fn (Student $record) => $record->results()->exists()
                                ? round($record->results()->min('percentage'), 1) . '%'
                                : '-')
                            ->color('danger'),
                        TextEntry::make('passed')
                            ->label(__('app.student.passed'))
                            ->state(fn (Student $record) => $record->results()->where('status', 'pass')->count())
                            ->badge()->color('success'),
                        TextEntry::make('failed')
                            ->label(__('app.student.failed'))
                            ->state(fn (Student $record) => $record->results()->where('status', 'fail')->count())
                            ->badge()->color('danger'),
                    ]),
                ]),
        ]);
    }
}
