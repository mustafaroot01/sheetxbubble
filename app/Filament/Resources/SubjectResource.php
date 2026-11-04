<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.settings');
    }

    public static function getModelLabel(): string
    {
        return __('app.subject.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.subject.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('app.subject.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('code')
                ->label(__('app.subject.code'))
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            Forms\Components\Select::make('stages')
                ->label(__('app.stage.plural'))
                ->relationship('stages', 'name')
                ->multiple()
                ->preload()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('app.subject.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label(__('app.subject.code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stages.name')->label(__('app.stage.plural'))->badge()->separator(', '),
                Tables\Columns\TextColumn::make('exams_count')->counts('exams')->label(__('app.subject.exams_count')),
                Tables\Columns\TextColumn::make('created_at')->label(__('app.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
