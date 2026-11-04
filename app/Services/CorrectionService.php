<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Facades\Http;

class CorrectionService
{
    public function correctSheet(string $imagePath, Exam $exam): array
    {
        $pythonApiUrl = config('services.python_omr.url', 'http://127.0.0.1:5000');

        $response = Http::timeout(30)
            ->attach('image', file_get_contents($imagePath), 'sheet.jpg')
            ->post($pythonApiUrl . '/api/correct', [
                'model_answer' => json_encode($exam->model_answer),
                'question_count' => $exam->question_count,
                'total_mark' => $exam->total_mark,
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->json('message', 'Failed to connect to correction service'));
        }

        $data = $response->json('data');

        if (!$data) {
            throw new \Exception('Invalid response from correction service');
        }

        return $data;
    }

    public function saveResult(array $correctionData, Exam $exam, ?string $imagePath = null): Result
    {
        $studentId = $correctionData['qr_data']['student_id'] ?? null;

        $student = Student::where('student_id', $studentId)->first();

        if (!$student) {
            throw new \Exception("Student with ID '{$studentId}' not found");
        }

        $existing = Result::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->first();

        if ($existing) {
            throw new \Exception("Student '{$student->full_name}' already has a result for this exam");
        }

        $details = [];
        $modelAnswer = $exam->model_answer;
        $studentAnswers = $correctionData['answers'];
        $correctCount = 0;
        $totalScore = 0;

        for ($i = 0; $i < $exam->question_count; $i++) {
            $isCorrect = isset($studentAnswers[$i]) && isset($modelAnswer[$i])
                && $studentAnswers[$i] === $modelAnswer[$i];

            $mark = $exam->getMarkForQuestion($i + 1);
            $earned = $isCorrect ? $mark : 0;

            if ($isCorrect) {
                $correctCount++;
            }
            $totalScore += $earned;

            $details[] = [
                'q' => $i + 1,
                'student_answer' => $studentAnswers[$i] ?? null,
                'correct_answer' => $modelAnswer[$i] ?? null,
                'is_correct' => $isCorrect,
                'mark' => $mark,
                'earned' => $earned,
            ];
        }

        $wrongCount = $exam->question_count - $correctCount;
        $percentage = $exam->total_mark > 0 ? round(($totalScore / $exam->total_mark) * 100, 2) : 0;
        $status = $percentage >= $exam->pass_mark ? 'pass' : 'fail';

        return Result::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => $totalScore,
            'total_mark' => $exam->total_mark,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'percentage' => $percentage,
            'status' => $status,
            'answer_details' => $details,
            'sheet_image_path' => $imagePath,
        ]);
    }
}
