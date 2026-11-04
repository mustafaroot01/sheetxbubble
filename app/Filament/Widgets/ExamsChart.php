<?php

namespace App\Filament\Widgets;

use App\Models\Result;
use Filament\Widgets\ChartWidget;

class ExamsChart extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('app.stats.results_chart');
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher');

        $months = [];
        $passData = [];
        $failData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M Y');

            $query = Result::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            if ($isTeacher) {
                $query->whereHas('exam', fn ($q) => $q->accessibleBy($user));
            }

            $passData[] = (clone $query)->where('status', 'pass')->count();
            $failData[] = (clone $query)->where('status', 'fail')->count();
        }

        return [
            'datasets' => [
                [
                    'label' => __('app.result.pass'),
                    'data' => $passData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => __('app.result.fail'),
                    'data' => $failData,
                    'backgroundColor' => 'rgba(244, 63, 94, 0.2)',
                    'borderColor' => 'rgb(244, 63, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
