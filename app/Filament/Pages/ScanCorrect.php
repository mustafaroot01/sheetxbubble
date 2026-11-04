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

    /**
     * Fast validation + student lookup only (no bubble processing), so the
     * UI can show student info immediately before the slow OMR runs.
     * Result is delivered via a browser event (method returns and property
     * sync are unreliable inside Filament pages; dispatched events work).
     */
    public function lookupStudent(string $qrJson): void
    {
        $payload = json_encode($this->resolveLookup($qrJson), JSON_UNESCAPED_UNICODE);
        // Livewire's own events don't reach the Alpine listener reliably in
        // Filament pages, so fire a native browser event via $this->js().
        $this->js('window.dispatchEvent(new CustomEvent("scan-lookup",{detail:{payload:' . $payload . '}}))');
    }

    private function resolveLookup(string $qrJson): array
    {
        $qrData = json_decode($qrJson, true);
        if (!$qrData || !isset($qrData['exam_id'], $qrData['student_id'])) {
            return ['error' => __('app.scan.qr_not_found')];
        }

        $exam = Exam::with(['subject', 'stage', 'studyType'])->find($qrData['exam_id']);
        if (!$exam) {
            return ['error' => __('app.scan.exam_not_found') . ' (ID: ' . $qrData['exam_id'] . ')'];
        }

        $user = auth()->user();
        if (!$exam->isAccessibleBy($user)) {
            return ['error' => __('app.scan.not_your_exam')];
        }

        if ($exam->status !== 'open') {
            return ['error' => __('app.scan.exam_closed') . ' (' . $exam->subject->name . ')'];
        }

        $student = Student::where('student_id', $qrData['student_id'])->first();
        if (!$student) {
            return ['error' => __('app.scan.student_not_found', ['id' => $qrData['student_id']])];
        }

        $existing = Result::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();
        if ($existing) {
            return ['error' => __('app.scan.already_corrected', ['name' => $student->full_name])];
        }

        return [
            'student_name' => $student->full_name,
            'student_id' => $student->student_id,
            'exam_name' => $exam->subject->name . ' - ' . $exam->stage->name,
            'question_count' => $exam->question_count,
        ];
    }

    public function processImageWithQr(string $imageData, string $qrJson): void
    {
        $payload = json_encode($this->resolveCorrection($imageData, $qrJson), JSON_UNESCAPED_UNICODE);
        $this->js('window.dispatchEvent(new CustomEvent("scan-final",{detail:{payload:' . $payload . '}}))');
    }

    private function resolveCorrection(string $imageData, string $qrJson): array
    {
        $this->scanResult = null;
        $this->showResult = false;
        $this->overlayImage = null;
        $this->errorMessage = null;

        try {
            $qrData = json_decode($qrJson, true);
            if (!$qrData || !isset($qrData['exam_id'], $qrData['student_id'])) {
                return ['error' => __('app.scan.qr_not_found')];
            }

            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageBytes = base64_decode($base64);

            if (!$imageBytes || strlen($imageBytes) < 1000) {
                return ['error' => __('app.scan.invalid_image')];
            }

            $exam = Exam::with(['subject', 'stage', 'studyType'])->find($qrData['exam_id']);
            if (!$exam) {
                return ['error' => __('app.scan.exam_not_found') . ' (ID: ' . $qrData['exam_id'] . ')'];
            }

            $user = auth()->user();
            if (!$exam->isAccessibleBy($user)) {
                return ['error' => __('app.scan.not_your_exam')];
            }

            if ($exam->status !== 'open') {
                return ['error' => __('app.scan.exam_closed') . ' (' . $exam->subject->name . ')'];
            }

            $student = Student::where('student_id', $qrData['student_id'])->first();
            if (!$student) {
                return ['error' => __('app.scan.student_not_found', ['id' => $qrData['student_id']])];
            }

            $existing = Result::where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->first();
            if ($existing) {
                return ['error' => __('app.scan.already_corrected', ['name' => $student->full_name])];
            }

            return $this->correctAndSave($imageBytes, $exam, $student);

        } catch (\Exception $e) {
            Log::error('ScanCorrect QR error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
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
            if (!$exam->isAccessibleBy($user)) {
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

            $outcome = $this->correctAndSave($imageBytes, $exam, $student);

            if (isset($outcome['error'])) {
                $this->errorMessage = $outcome['error'];
                return;
            }

            $this->scanResult = $outcome['result'];
            $this->overlayImage = $outcome['overlay'];
            $this->showResult = true;

        } catch (\Exception $e) {
            Log::error('ScanCorrect error: ' . $e->getMessage());
            $this->errorMessage = $e->getMessage();
        }
    }

    private function correctAndSave(string $imageBytes, Exam $exam, Student $student): array
    {
        $pythonApiUrl = config('services.python_omr.url', 'http://127.0.0.1:5001');

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
            return ['error' => __('app.scan.correction_failed') . ' - ' . __('app.scan.service_unavailable')];
        }

        if (!$response->successful()) {
            return ['error' => $response->json('message', __('app.scan.correction_failed'))];
        }

        $data = $response->json('data');

        if (!$data || !isset($data['correct_count'])) {
            return ['error' => __('app.scan.correction_failed')];
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

        Notification::make()
            ->title(__('app.correction.success'))
            ->body($student->full_name . ': ' . $score . '/' . $exam->total_mark . ' (' . $percentage . '%)')
            ->success()
            ->send();

        return ['result' => $resultData, 'overlay' => $data['overlay_image'] ?? null];
    }

    public function resetScan(): void
    {
        $this->scanResult = null;
        $this->showResult = false;
        $this->overlayImage = null;
        $this->errorMessage = null;
    }
}
