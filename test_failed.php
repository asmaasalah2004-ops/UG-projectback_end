<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::first();
echo "Student Dept: " . $student->department_id . "\n";
$failed = DB::table('student_courses')->where('student_id', $student->id)->where('status', 'Failed')->get();
echo "Failed Courses: " . $failed->count() . "\n";

if ($failed->count() > 0) {
    foreach ($failed as $f) {
        $fc = $f->courses_id;
        echo "Failed Course ID: " . $fc . "\n";
        $inDept = DB::table('course_departments')->where('department_id', $student->department_id)->where('course_id', $fc)->exists();
        echo "Is in Dept: " . ($inDept ? 'Yes' : 'No') . "\n";
    }
}
