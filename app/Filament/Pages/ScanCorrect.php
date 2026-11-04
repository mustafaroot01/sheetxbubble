<?php

namespace App\Filament\Pages;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanCorrect extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav.exams');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.scan.title');
    }

    public function getTitle(): string
    {
        return __('app.scan.title');
    }

    protected static string $view = 'filament.pages.scan-correct';

    public ?array $scanResult = null;
    public bool $showResult = false;
    public ?string $overlayImage = null;
    public ?string $errorMessage = null;

    public function processImageWithQr(string $imageData, string $qrJson): void
    {
        $this->scanResult = null;
        $this->showResult = false;
        $this->overlayImage = null;
        $this->errorMessage = null;

        try {
            $qrData = json_decode($qrJson, true);
            if (!$qrData || !isset($qrData['exam_id'], $qrData['student_id'])) {
                $this->setError(__('app.scan.qr_not_found'));
                return;
            }

            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageBytes = base64_decode($base64);

            if (!$imageBytes || strlen($imageBytes) < 1000) {
                $this->setError(__('app.scan.invalid_image'));
                return;
            }

            $exam = Exam::with(['subject', 'stage', 'studyType'])->find($qrData['exam_id']);
            if (!$exam) {
                $this->setError(__('app.scan.exam_not_found') . ' (ID: ' . $qrData['exam_id'] . ')');
                return;
            }

            $user = auth()->user();
            if ($user->hasRole('teacher') && $exam->teacher_id !== $user->id) {
                $this->setError(__('app.scan.not_your_exam'));
                return;
            }

            if ($exam->status !== 'open') {
                $this->setError(__('app.scan.exam_closed') . ' (' . $exam->subject->name . ')');
                return;
            }

            $student = Student::where('student_id', $qrData['student_id'])->first();
            if (!$student) {
                $this->setError(__('app.scan.student_not_found', ['id' => $qrData['student_id']]));
                return;
            }

            $existing = Result::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            if ($existing) {
                $this->setError(__('app.scan.already_corrected', ['name' => $student->full_name]));
                return;
            }

            $this->dispatch('scan-student-info', [
                'student_name' => $student->full_name,
                'student_id' => $student->student_id,
                'exam_name' => $exam->subject->name . ' - ' . $exam->stage->name,
                'question_count' => $exam->question_count,
            ]);

            $this->correctAndSave($imageBytes, $exam, $student, true);

        } catch (\Exception $e) {
            Log::error('ScanCorrect QR error: ' . $e->getMessage());
            $this->setError($e->getMessage());
        }
    }

    private function setError(string $message): void
    {
        $this->errorMessage = $message;
        $this->dispatch('scan-error', $message);
    }

    public function processImage(string $imageData): void
    {
        $this->scanResult = null;
        $this->showResult = false;
        $this->overlayImage = null;
        $this->errorMessage = null;

        try {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageBytes = base64_decode($base64);

            if (!$imageBytes || strlen($imageBytes) < 1000) {
                $this->errorMessage = __('app.scan.invalid_image');
                return;
            }

            $pythonApiUrl = config('services.python_omr.url', 'http://127.0.0.1:5001');

            // Fast QR-only read (no bubble processing)
            try {
                $qrResponse = Http::timeout(10)
                    ->attach('image', $imageBytes, 'scan.jpg')
                    ->post($pythonApiUrl . '/api/read-qr');
            } catch (\Exception $e) {
                $this->errorMessage = __('app.scan.correction_failed') . ' - ' . __('app.scan.service_unavailable');
                return;
            }

            if (!$qrResponse->successful()) {
                $this->errorMessage = __('app.scan.qr_not_found');
                return;
            }

            $qrData = $qrResponse->json('data.qr_data');

            if (!$qrData || !isset($qrData['exam_id'])) {
                $this->errorMessage = __('app.scan.qr_not_found');
                return;
            }

            // Validate exam
            $exam = Exam::with(['subject', 'stage', 'studyType'])->find($qrData['exam_id']);
            if (!$exam) {
                $this->errorMessage = __('app.scan.exam_not_found') . ' (ID: ' . $qrData['exam_id'] . ')';
                return;
            }

            $user = auth()->user();
            if ($user->hasRole('teacher') && $exam->teacher_id !== $user->id) {
                $this->errorMessage = __('app.scan.not_your_exam');
                return;
            }

            if ($exam->status !== 'open') {
                $this->errorMessage = __('app.scan.exam_closed') . ' (' . $exam->subject->name . ')';
                return;
            }

            // Validate student
            $student = Student::where('student_id', $qrData['student_id'])->first();
            if (!$student) {
                $this->errorMessage = __('app.scan.student_not_found', ['id' => $qrData['student_id']]);
                return;
            }

            // Check for duplicate
            $existing = Result::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            if ($existing) {
                $this->errorMessage = __('app.scan.already_corrected', ['name' => $student->full_name]);
                return;
            }

            $this->correctAndSave($imageBytes, $exam, $student);

        } catch (\Exception $e) {
            Log::error('ScanCorrect error: ' . $e->getMessage());
            $this->errorMessage = $e->getMessage();
        }
    }

    private function correctAndSave(string $imageBytes, Exam $exam, Student $student, bool $fromCamera = false): void
    {
        $pythonApiUrl = config('services.python_omr.url', 'http://127.0.0.1:5001');

        $setErr = function (string $msg) use ($fromCamera) {
            $this->errorMessage = $msg;
            if ($fromCamera) {
                $this->dispatch('scan-error', $msg);
            }
        };

        try {
            $response = Http::timeout(30)
                ->attach('image', $imageBytes, 'scan.jpg')
                ->post($pythonApiUrl . '/api/correct-scan', [
                    'model_answer' => json_encode($exam->model_answer),
                    'question_count' => $exam->question_count,
                    'choices_count' => $exam->choices_count ?? 4,
                    'total_mark' => $exam->total_mark,
                ]);
        } catch (\Exception $e) {
            $setErr(__('app.scan.correction_failed') . ' - ' . __('app.scan.service_unavailable'));
            return;
        }

        if (!$response->successful()) {
            $setErr($response->json('message', __('app.scan.correction_failed')));
            return;
        }

        $data = $response->json('data');

        if (!$data || !isset($data['correct_count'])) {
            $setErr(__('app.scan.correction_failed'));
            return;
        }

        $correctCount = $data['correct_count'];
        $wrongCount = $data['wrong_count'];
        $score = $data['score'];
        $percentage = $data['percentage'];
        $status = $percentage >= $exam->pass_mark ? 'pass' : 'fail';

        Result::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => $score,
            'total_mark' => $exam->total_mark,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'percentage' => $percentage,
            'status' => $status,
            'answer_details' => $data['details'],
        ]);

        $this->overlayImage = $data['overlay_image'] ?? null;

        $resultData = [
            'student_name' => $student->full_name,
            'student_id' => $student->student_id,
            'exam_name' => $exam->subject->name . ' - ' . $exam->stage->name,
            'score' => $score,
            'total_mark' => $exam->total_mark,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'percentage' => $percentage,
            'status' => $status,
            'details' => $data['details'],
        ];

        $this->scanResult = $resultData;

        if ($fromCamera) {
            $this->dispatch('scan-result', $resultData);
        } else {
            $this->showResult = true;
        }

        Notification::make()
            ->title(__('app.correction.success'))
            ->body($student->full_name . ': ' . $score . '/' . $exam->total_mark . ' (' . $percentage . '%)')
            ->success()
            ->send();
    }

    public function resetScan(): void
    {
        $this->scanResult = null;
        $this->showResult = false;
        $this->overlayImage = null;
        $this->errorMessage = null;
    }
}
