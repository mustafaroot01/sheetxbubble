<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    protected static ?string $icon = 'heroicon-o-academic-cap';

    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.student.exam_history');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('exam.subject')
                ->join('exams', 'exams.id', '=', 'results.exam_id')
                ->select('results.*', 'exams.exam_date as exam_date'))
            ->defaultSort('exam_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('exam.subject.name')
                    ->label(__('app.result.subject'))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('exam_date')
                    ->label(__('app.exam.exam_date'))
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label(__('app.result.score'))
                    ->formatStateUsing(fn ($state, $record) => $state . ' / ' . $record->total_mark)
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label(__('app.result.percentage'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('correct_count')
                    ->label(__('app.result.correct_count'))
                    ->color('success'),
                Tables\Columns\TextColumn::make('wrong_count')
                    ->label(__('app.result.wrong_count'))
                    ->color('danger'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.result.status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pass' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'pass' ? __('app.result.pass') : __('app.result.fail')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.result.status'))
                    ->options([
                        'pass' => __('app.result.pass'),
                        'fail' => __('app.result.fail'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label(__('app.result.answer_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record->exam->subject->name . ' - ' . $record->exam->exam_date->format('Y-m-d'))
                    ->modalContent(fn ($record) => view('filament.partials.answer-details', ['details' => $record->answer_details]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('app.back')),
            ])
            ->emptyStateHeading(__('app.student.no_exams'));
    }
}
