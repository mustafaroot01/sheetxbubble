<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.user_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.user.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.user.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('app.user.name'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label(__('app.user.email'))
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\TextInput::make('password')
                ->label(__('app.user.password'))
                ->password()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->maxLength(255),
            Forms\Components\Select::make('roles')
                ->label(__('app.user.roles'))
                ->relationship('roles', 'name')
                ->required()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('app.user.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label(__('app.user.email'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles.name')->label(__('app.user.roles'))->badge()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('app.created_at'))->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
