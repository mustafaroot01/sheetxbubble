<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    /**
     * Plain JSON endpoint for the scan page. Bypasses Livewire entirely so
     * the result reliably reaches the browser via a standard fetch().
     * Validates the QR, runs OMR through the Python service, saves the
     * result, and returns everything as JSON.
     */
    public function process(Request $request): JsonResponse
    {
        $qr = (string) $request->input('qr', '');
        $imageData = (string) $request->input('image', '');

        $qrData = $this->parseQr($qr);
        if (!$qrData) {
            return response()->json(['ok' => false, 'error' => __('app.scan.qr_not_found')]);
        }

        $exam = Exam::with(['subject', 'stage', 'studyType'])->find($qrData['exam_id']);
        if (!$exam) {
            return response()->json(['ok' => false, 'error' => __('app.scan.exam_not_found') . ' (ID: ' . $qrData['exam_id'] . ')']);
        }

        $user = $request->user();
        if (!$exam->isAccessibleBy($user)) {
            return response()->json(['ok' => false, 'error' => __('app.scan.not_your_exam')]);
        }

        if ($exam->status !== 'open') {
            return response()->json(['ok' => false, 'error' => __('app.scan.exam_closed') . ' (' . $exam->subject->name . ')']);
        }

        $student = Student::where('student_id', $qrData['student_id'])->first();
        if (!$student) {
            return response()->json(['ok' => false, 'error' => __('app.scan.student_not_found', ['id' => $qrData['student_id']])]);
        }

        $existing = Result::where('student_id', $student->id)->where('exam_id', $exam->id)->first();
        if ($existing) {
            return response()->json(['ok' => false, 'error' => __('app.scan.already_corrected', ['name' => $student->full_name])]);
        }

        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageBytes = base64_decode($base64);
        if (!$imageBytes || strlen($imageBytes) < 1000) {
            return response()->json(['ok' => false, 'error' => __('app.scan.invalid_image')]);
        }

        $pythonUrl = config('services.python_omr.url', 'http://127.0.0.1:5001');

        try {
            $response = Http::timeout(30)
                ->attach('image', $imageBytes, 'scan.jpg')
                ->post($pythonUrl . '/api/correct-scan', [
                    'model_answer' => json_encode($exam->model_answer),
                    'question_count' => $exam->question_count,
                    'choices_count' => $exam->choices_count ?? 4,
                    'total_mark' => $exam->total_mark,
                ]);
        } catch (\Throwable $e) {
            Log::error('Scan OMR connect error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => __('app.scan.service_unavailable')]);
        }

        if (!$response->successful()) {
            return response()->json(['ok' => false, 'error' => $response->json('message', __('app.scan.correction_failed'))]);
        }

        $data = $response->json('data');
        if (!$data || !isset($data['correct_count'])) {
            return response()->json(['ok' => false, 'error' => __('app.scan.correction_failed')]);
        }

        $percentage = $data['percentage'];
        $status = $percentage >= $exam->pass_mark ? 'pass' : 'fail';

        Result::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'score' => $data['score'],
            'total_mark' => $exam->total_mark,
            'correct_count' => $data['correct_count'],
            'wrong_count' => $data['wrong_count'],
            'percentage' => $percentage,
            'status' => $status,
            'answer_details' => $data['details'],
        ]);

        return response()->json([
            'ok' => true,
            'student' => [
                'name' => $student->full_name,
                'id' => $student->student_id,
                'exam' => $exam->subject->name . ' - ' . $exam->stage->name,
            ],
            'result' => [
                'score' => $data['score'],
                'total_mark' => $exam->total_mark,
                'correct_count' => $data['correct_count'],
                'wrong_count' => $data['wrong_count'],
                'percentage' => $percentage,
                'status' => $status,
                'details' => $data['details'],
                'overlay' => $data['overlay_image'] ?? null,
            ],
            'debug' => $data['debug'] ?? null,
        ]);
    }

    private function parseQr(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'E') && str_contains($raw, '|')) {
            $out = [];
            foreach (explode('|', $raw) as $part) {
                $key = substr($part, 0, 1);
                $val = substr($part, 1);
                if ($key === 'E') $out['exam_id'] = $val;
                elseif ($key === 'S') $out['student_id'] = $val;
                elseif ($key === 'G') $out['stage_id'] = $val;
                elseif ($key === 'T') $out['study_type_id'] = $val;
                elseif ($key === 'P') $out['page'] = $val;
            }
            return isset($out['exam_id'], $out['student_id']) ? $out : null;
        }

        $json = json_decode($raw, true);
        if (is_array($json) && isset($json['exam_id'], $json['student_id'])) {
            return $json;
        }

        return null;
    }
}
