<?php

namespace App\Filament\Pages;

use App\Models\Exam;
use App\Services\CorrectionService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class CorrectSheet extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.exams');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.correction.title');
    }

    public function getTitle(): string
    {
        return __('app.correction.title');
    }

    protected static string $view = 'filament.pages.correct-sheet';

    public ?int $exam_id = null;
    public $sheet_image = null;
    public ?array $correctionResult = null;
    public bool $showResult = false;

    public function mount(): void
    {
        $examId = request()->query('exam_id');
        if ($examId && Exam::find($examId)) {
            $this->exam_id = (int) $examId;
        }
        $this->form->fill([
            'exam_id' => $this->exam_id,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('teacher');

        return $form->schema([
            Forms\Components\Select::make('exam_id')
                ->label(__('app.correction.select_exam'))
                ->options(function () use ($user, $isTeacher) {
                    $query = Exam::with(['subject', 'stage', 'studyType'])
                        ->where('status', 'open');
                    if ($isTeacher) {
                        $query->accessibleBy($user);
                    }
                    return $query->get()->mapWithKeys(fn ($exam) =>
                        [$exam->id => $exam->subject->name . ' - ' . $exam->stage->name . ' - ' . $exam->studyType->name . ' (' . $exam->exam_date->format('Y-m-d') . ')']
                    );
                })
                ->required()
                ->live()
                ->searchable(),

            Forms\Components\FileUpload::make('sheet_image')
                ->label(__('app.correction.upload_sheet'))
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png'])
                ->directory('sheets')
                ->visible(fn () => $this->exam_id !== null)
                ->required(),
        ]);
    }

    public function correct(): void
    {
        $this->validate([
            'exam_id' => 'required|exists:exams,id',
            'sheet_image' => 'required',
        ]);

        $exam = Exam::findOrFail($this->exam_id);
        $sheetImage = is_array($this->sheet_image) ? reset($this->sheet_image) : $this->sheet_image;

        if ($sheetImage instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $storedPath = $sheetImage->store('sheets', 'public');
            $imagePath = Storage::disk('public')->path($storedPath);
        } else {
            $storedPath = $sheetImage;
            $imagePath = Storage::disk('public')->path($sheetImage);
        }

        try {
            $service = new CorrectionService();
            $correctionData = $service->correctSheet($imagePath, $exam);
            $result = $service->saveResult($correctionData, $exam, $storedPath);

            $this->correctionResult = [
                'student_name' => $result->student->full_name,
                'student_id' => $result->student->student_id,
                'score' => $result->score,
                'total_mark' => $result->total_mark,
                'correct_count' => $result->correct_count,
                'wrong_count' => $result->wrong_count,
                'percentage' => $result->percentage,
                'status' => $result->status,
                'details' => $result->answer_details,
            ];
            $this->showResult = true;

            Notification::make()
                ->title(__('app.correction.success'))
                ->body($result->student->full_name . ': ' . $result->score . '/' . $result->total_mark)
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('app.correction.failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function correctAnother(): void
    {
        $this->sheet_image = null;
        $this->correctionResult = null;
        $this->showResult = false;
    }
}
