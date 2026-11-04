<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherAssignmentResource\Pages;
use App\Models\TeacherAssignment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherAssignmentResource extends Resource
{
    protected static ?string $model = TeacherAssignment::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = null;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.teacher_assignment.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.teacher_assignment.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        $isCreate = $form->getOperation() === 'create';

        return $form->schema([
            Forms\Components\Select::make('teacher_id')
                ->label(__('app.teacher_assignment.teacher'))
                ->options(fn () => User::role('teacher')->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload(),

            $isCreate
                ? Forms\Components\Select::make('stage_ids')
                    ->label(__('app.teacher_assignment.stage'))
                    ->options(fn () => \App\Models\Stage::pluck('name', 'id'))
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('subject_id', null))
                : Forms\Components\Select::make('stage_id')
                    ->label(__('app.teacher_assignment.stage'))
                    ->options(fn () => \App\Models\Stage::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('subject_id', null)),

            Forms\Components\Select::make('subject_id')
                ->label(__('app.teacher_assignment.subject'))
                ->options(function (Get $get) use ($isCreate) {
                    $stageIds = $isCreate ? ($get('stage_ids') ?? []) : array_filter([(int) $get('stage_id')]);
                    if (empty($stageIds)) return [];
                    return \App\Models\Subject::whereHas('stages', fn ($q) => $q->whereIn('stages.id', $stageIds))
                        ->pluck('name', 'id');
                })
                ->required()
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label(__('app.teacher_assignment.teacher'))
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('app.teacher_assignment.subject'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label(__('app.teacher_assignment.stage'))
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')->label(__('app.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('teacher.name')
                    ->label(__('app.teacher_assignment.teacher'))
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('teacher.name')
            ->filters([
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->label(__('app.teacher_assignment.teacher')),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label(__('app.teacher_assignment.subject')),
                Tables\Filters\SelectFilter::make('stage_id')
                    ->relationship('stage', 'name')
                    ->label(__('app.teacher_assignment.stage')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherAssignments::route('/'),
            'create' => Pages\CreateTeacherAssignment::route('/create'),
            'edit' => Pages\EditTeacherAssignment::route('/{record}/edit'),
        ];
    }
}
