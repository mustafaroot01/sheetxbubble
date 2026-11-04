<?php
// Standalone diagnostic — run on the server:  php diagnose.php
// Prints whether the new code is loaded and why a teacher may not see an exam.

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Filament\Pages\ScanCorrect;
use App\Models\Exam;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;

echo "==== CODE VERSION CHECK ====\n";
echo 'ScanCorrect::lookupStudent exists : ' . (method_exists(ScanCorrect::class, 'lookupStudent') ? 'YES' : 'NO') . "\n";
echo 'Exam::accessibleBy scope exists   : ' . (method_exists(Exam::class, 'scopeAccessibleBy') ? 'YES' : 'NO') . "\n";

echo "\n==== EXAMS ====\n";
foreach (Exam::all() as $e) {
    echo "exam {$e->id}: teacher_id={$e->teacher_id} stage_id={$e->stage_id} subject_id={$e->subject_id} status={$e->status}\n";
}

echo "\n==== TEACHERS & ACCESS ====\n";
foreach (User::all() as $u) {
    if (!$u->hasRole('teacher')) {
        continue;
    }
    echo "teacher {$u->id} ({$u->email})\n";
    $assigns = TeacherAssignment::where('teacher_id', $u->id)->get();
    if ($assigns->isEmpty()) {
        echo "   assignments: NONE  <-- this teacher is not assigned to any stage/subject\n";
    }
    foreach ($assigns as $a) {
        echo "   assignment: stage_id={$a->stage_id} subject_id={$a->subject_id}\n";
    }
    $ids = Exam::accessibleBy($u)->pluck('id')->implode(', ');
    echo "   exams accessible: [" . ($ids ?: 'NONE') . "]\n";
}

echo "\n==== STUDENTS (first 10 ids) ====\n";
echo Student::limit(10)->pluck('student_id')->implode(', ') . "\n";

echo "\nDone.\n";
