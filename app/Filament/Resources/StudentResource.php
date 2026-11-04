<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.academic');
    }

    public static function getModelLabel(): string
    {
        return __('app.student.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.student.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('full_name')
                ->label(__('app.student.full_name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('student_id')
                ->label(__('app.student.student_id'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50),
            Forms\Components\Select::make('stage_id')
                ->label(__('app.student.stage'))
                ->relationship('stage', 'name')
                ->required()
                ->preload(),
            Forms\Components\Select::make('study_type_id')
                ->label(__('app.student.study_type'))
                ->relationship('studyType', 'name')
                ->required()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_id')->label(__('app.student.student_id'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('full_name')->label(__('app.student.full_name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stage.name')->label(__('app.student.stage'))->sortable(),
                Tables\Columns\TextColumn::make('studyType.name')->label(__('app.student.study_type'))->sortable(),
                Tables\Columns\TextColumn::make('results_count')->counts('results')->label(__('app.student.exams_taken')),
                Tables\Columns\TextColumn::make('created_at')->label(__('app.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage_id')
                    ->relationship('stage', 'name')
                    ->label(__('app.student.stage')),
                Tables\Filters\SelectFilter::make('study_type_id')
                    ->relationship('studyType', 'name')
                    ->label(__('app.student.study_type')),
            ])
            ->recordUrl(fn (Student $record) => static::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label(__('app.student.profile'))
                        ->icon('heroicon-o-user-circle')
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StudentResource\RelationManagers\ResultsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
