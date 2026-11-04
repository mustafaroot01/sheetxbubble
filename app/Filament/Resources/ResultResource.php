<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultResource\Pages;
use App\Models\Result;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResultResource extends Resource
{
    protected static ?string $model = Result::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.exams');
    }

    public static function getModelLabel(): string
    {
        return __('app.result.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.result.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasRole('teacher')) {
            $query->whereHas('exam', fn (Builder $q) => $q->where('teacher_id', $user->id));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label(__('app.result.student_name'))
                ->relationship('student', 'full_name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('exam_id')
                ->label(__('app.exam.label'))
                ->relationship('exam', 'id')
                ->required()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->subject->name . ' - ' . $record->stage->name . ' (' . $record->exam_date->format('Y-m-d') . ')'),
            Forms\Components\TextInput::make('score')->label(__('app.result.score'))->required()->numeric(),
            Forms\Components\TextInput::make('total_mark')->label(__('app.result.total_mark'))->required()->numeric(),
            Forms\Components\TextInput::make('correct_count')->label(__('app.result.correct_count'))->required()->numeric(),
            Forms\Components\TextInput::make('wrong_count')->label(__('app.result.wrong_count'))->required()->numeric(),
            Forms\Components\TextInput::make('percentage')->label(__('app.result.percentage'))->required()->numeric()->suffix('%'),
            Forms\Components\Select::make('status')
                ->label(__('app.result.status'))
                ->options([
                    'pass' => __('app.result.pass'),
                    'fail' => __('app.result.fail'),
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')->label(__('app.result.student_name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.student_id')->label(__('app.result.student_id'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('exam.subject.name')->label(__('app.result.subject'))->sortable(),
                Tables\Columns\TextColumn::make('exam.stage.name')->label(__('app.result.stage'))->sortable(),
                Tables\Columns\TextColumn::make('score')->label(__('app.result.score'))->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state . '/' . $record->total_mark),
                Tables\Columns\TextColumn::make('percentage')->label(__('app.result.percentage'))->sortable()->suffix('%'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.result.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pass' => 'success',
                        'fail' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => $state === 'pass' ? __('app.result.pass') : __('app.result.fail')),
                Tables\Columns\TextColumn::make('created_at')->label(__('app.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exam_id')
                    ->relationship('exam', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->subject->name . ' - ' . $record->exam_date->format('Y-m-d'))
                    ->label(__('app.exam.label')),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.result.status'))
                    ->options([
                        'pass' => __('app.result.pass'),
                        'fail' => __('app.result.fail'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip(__('app.actions')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResults::route('/'),
        ];
    }
}
