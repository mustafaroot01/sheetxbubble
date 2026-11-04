<?php

namespace App\Filament\Widgets;

use App\Models\Result;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestResultsTable extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): ?string
    {
        return __('app.stats.latest_results');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Result::query()
                    ->with(['student', 'exam.subject'])
                    ->when(
                        auth()->user()->hasRole('teacher'),
                        fn (Builder $q) => $q->whereHas('exam', fn ($q2) => $q2->accessibleBy(auth()->user()))
                    )
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('app.result.student_name'))
                    ->weight('bold')
                    ->limit(20),
                Tables\Columns\TextColumn::make('exam.subject.name')
                    ->label(__('app.result.subject'))
                    ->limit(15),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('%')
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 50 ? 'success' : 'danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.result.status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pass' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'pass' ? __('app.result.pass') : __('app.result.fail')),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
