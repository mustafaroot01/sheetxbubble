<?php

namespace App\Filament\Pages;

use App\Models\Exam;
use App\Models\SheetDesign;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class GenerateSheets extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.exams');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.sheet.title');
    }

    public function getTitle(): string
    {
        return __('app.sheet.title');
    }

    protected static string $view = 'filament.pages.generate-sheets';

    public ?int $exam_id = null;
    public ?array $selected_students = [];
    public bool $select_all = true;
    public ?int $studentCount = null;

    public function mount(): void
    {
        $examId = request()->query('exam_id');
        if ($examId && ($exam = Exam::find($examId))) {
            $this->exam_id = (int) $examId;
            $this->studentCount = Student::where('stage_id', $exam->stage_id)
                ->where('study_type_id', $exam->study_type_id)
                ->count();
        }
        $this->form->fill([
            'exam_id' => $this->exam_id,
            'select_all' => $this->select_all,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher');

        return $form->schema([
            Forms\Components\Select::make('exam_id')
                ->label(__('app.sheet.select_exam'))
                ->options(function () use ($user, $isTeacher) {
                    $query = Exam::with(['subject', 'stage', 'studyType'])
                        ->where('status', 'open');
                    if ($isTeacher) {
                        $query->where('teacher_id', $user->id);
                    }
                    return $query->get()->mapWithKeys(fn ($exam) =>
                        [$exam->id => $exam->subject->name . ' - ' . $exam->stage->name . ' - ' . $exam->studyType->name . ' (' . $exam->exam_date->format('Y-m-d') . ')']
                    );
                })
                ->required()
                ->live()
                ->searchable()
                ->afterStateUpdated(function ($state) {
                    if ($state) {
                        $exam = Exam::find($state);
                        if ($exam) {
                            $this->studentCount = Student::where('stage_id', $exam->stage_id)
                                ->where('study_type_id', $exam->study_type_id)
                                ->count();
                        }
                    } else {
                        $this->studentCount = null;
                    }
                    $this->select_all = true;
                    $this->selected_students = [];
                }),

            Forms\Components\Toggle::make('select_all')
                ->label(__('app.sheet.all_students'))
                ->default(true)
                ->live()
                ->visible(fn () => $this->exam_id !== null),

            Forms\Components\Select::make('selected_students')
                ->label(__('app.sheet.choose_students'))
                ->multiple()
                ->searchable()
                ->preload()
                ->options(function () {
                    if (!$this->exam_id) {
                        return [];
                    }
                    $exam = Exam::find($this->exam_id);
                    if (!$exam) {
                        return [];
                    }
                    return Student::where('stage_id', $exam->stage_id)
                        ->where('study_type_id', $exam->study_type_id)
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => $s->student_id . ' - ' . $s->full_name]);
                })
                ->visible(fn (Get $get) => $this->exam_id !== null && !$get('select_all')),
        ]);
    }

    public ?string $downloadUrl = null;

    public function generate(): void
    {
        $this->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $this->downloadUrl = null;
        $exam = Exam::with(['subject', 'stage', 'studyType'])->findOrFail($this->exam_id);

        if ($this->select_all) {
            $students = Student::where('stage_id', $exam->stage_id)
                ->where('study_type_id', $exam->study_type_id)
                ->get();
        } else {
            if (empty($this->selected_students)) {
                Notification::make()
                    ->title(__('app.sheet.no_students'))
                    ->danger()
                    ->send();
                return;
            }
            $students = Student::whereIn('id', $this->selected_students)->get();
        }

        if ($students->isEmpty()) {
            Notification::make()
                ->title(__('app.sheet.no_students'))
                ->danger()
                ->send();
            return;
        }

        $pythonApiUrl = config('services.python_omr.url', 'http://127.0.0.1:5001');

        try {
            $payload = [
                'exam_id' => $exam->id,
                'subject_name' => $exam->subject->name,
                'stage' => $exam->stage->name,
                'stage_id' => $exam->stage_id,
                'study_type' => $exam->studyType->name,
                'study_type_id' => $exam->study_type_id,
                'exam_date' => $exam->exam_date->format('Y-m-d'),
                'question_count' => $exam->question_count,
                'choices_count' => $exam->choices_count,
                'students' => $students->map(fn ($s) => [
                    'student_id' => $s->student_id,
                    'full_name' => $s->full_name,
                ])->values()->toArray(),
            ];

            $activeDesign = SheetDesign::getActive();
            if ($activeDesign) {
                $payload['design'] = $activeDesign->toDesignConfig();
            }

            $response = Http::timeout(60)->post($pythonApiUrl . '/api/generate-sheet', $payload);

            if (!$response->successful()) {
                $error = $response->json('message', 'Failed to generate sheets');
                throw new \Exception($error);
            }

            $filename = 'bubble_sheets_' . $exam->subject->name . '_' . now()->format('Y-m-d_H-i') . '.pdf';
            $savePath = 'generated/' . $filename;

            if (!is_dir(storage_path('app/public/generated'))) {
                mkdir(storage_path('app/public/generated'), 0755, true);
            }

            file_put_contents(storage_path('app/public/' . $savePath), $response->body());

            $this->downloadUrl = '/storage/' . $savePath;

            Notification::make()
                ->title(__('app.sheet.success'))
                ->body(__('app.sheet.generated_count', ['count' => $students->count()]))
                ->success()
                ->send();

            $this->js("window.open('{$this->downloadUrl}', '_blank')");

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.sheet.failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
