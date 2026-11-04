<?php

namespace App\Filament\Widgets;

use App\Models\Result;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubjectPerformanceChart extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return __('app.stats.subject_performance');
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher');

        $query = Result::query()
            ->join('exams', 'results.exam_id', '=', 'exams.id')
            ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
            ->select('subjects.name', DB::raw('ROUND(AVG(results.percentage), 1) as avg_pct'))
            ->groupBy('subjects.name');

        if ($isTeacher) {
            $query->where('exams.teacher_id', $user->id);
        }

        $results = $query->get();

        $colors = [
            'rgba(99, 102, 241, 0.7)',
            'rgba(16, 185, 129, 0.7)',
            'rgba(245, 158, 11, 0.7)',
            'rgba(244, 63, 94, 0.7)',
            'rgba(14, 165, 233, 0.7)',
            'rgba(168, 85, 247, 0.7)',
            'rgba(236, 72, 153, 0.7)',
            'rgba(34, 197, 94, 0.7)',
        ];

        return [
            'datasets' => [
                [
                    'data' => $results->pluck('avg_pct')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $results->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $results->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
