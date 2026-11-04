<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StageResource\Pages;
use App\Models\Stage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StageResource extends Resource
{
    protected static ?string $model = Stage::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.settings');
    }

    public static function getModelLabel(): string
    {
        return __('app.stage.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.stage.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('app.stage.name'))
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('app.stage.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subjects.name')->label(__('app.subject.plural'))->badge()->separator(', '),
                Tables\Columns\TextColumn::make('students_count')->counts('students')->label(__('app.student.plural')),
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
            'index' => Pages\ListStages::route('/'),
            'create' => Pages\CreateStage::route('/create'),
            'edit' => Pages\EditStage::route('/{record}/edit'),
        ];
    }
}
