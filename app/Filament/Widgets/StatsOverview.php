<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher');

        $studentsCount = Student::count();
        $examsQuery = Exam::query();
        $resultsQuery = Result::query();

        if ($isTeacher) {
            $examsQuery->where('teacher_id', $user->id);
            $resultsQuery->whereHas('exam', fn ($q) => $q->where('teacher_id', $user->id));
        }

        $examsCount = $examsQuery->count();
        $openExams = (clone $examsQuery)->where('status', 'open')->count();
        $resultsCount = $resultsQuery->count();
        $avgPercentage = $resultsQuery->avg('percentage') ?? 0;
        $passCount = (clone $resultsQuery)->where('status', 'pass')->count();
        $failCount = $resultsCount - $passCount;
        $passRate = $resultsCount > 0 ? round(($passCount / $resultsCount) * 100, 1) : 0;

        $lastWeekResults = Result::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make(__('app.stats.total_students'), number_format($studentsCount))
                ->description(__('app.stats.registered'))
                ->descriptionIcon('heroicon-m-user-group')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->chart($this->getWeeklyData(Student::class)),

            Stat::make(__('app.stats.total_exams'), number_format($examsCount))
                ->description($openExams . ' ' . __('app.exam.status_open'))
                ->descriptionIcon('heroicon-m-lock-open')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->chart($this->getWeeklyData(Exam::class)),

            Stat::make(__('app.stats.corrected_sheets'), number_format($resultsCount))
                ->description($lastWeekResults . ' ' . __('app.stats.this_week'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getWeeklyData(Result::class)),

            Stat::make(__('app.stats.average_score'), round($avgPercentage, 1) . '%')
                ->description(__('app.stats.from_all_results'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->icon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make(__('app.stats.pass_rate'), $passRate . '%')
                ->description($passCount . ' ' . __('app.result.pass') . ' / ' . $failCount . ' ' . __('app.result.fail'))
                ->descriptionIcon($passRate >= 50 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-academic-cap')
                ->color($passRate >= 50 ? 'success' : 'danger'),
        ];
    }

    private function getWeeklyData(string $model): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = $model::whereDate('created_at', now()->subDays($i))->count();
        }
        return $data;
    }
}
