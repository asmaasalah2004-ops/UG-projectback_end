<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::first();
echo "Student ID: " . $student->id . "\n";

$query = DB::table('student_courses as sc')
    ->join('semesters as sem', 'sem.id', '=', 'sc.semester_id')
    ->join('courses as c', 'c.id', '=', 'sc.courses_id')
    ->leftJoin('risk_evaluation as re', function ($join) {
        $join->on('re.student_id', '=', 'sc.student_id')
            ->on('re.semester_id', '=', 'sc.semester_id');
    })
    ->where('sc.student_id', $student->id)
    ->select(
        'c.course_code',
        'c.id as course_id',
        'sc.credit_hours',
        'sc.grade',
        'sc.status',
        'sc.attempt_number',
        're.semester_gpa',
        're.risk_level',
        'sem.id',
        DB::raw("CONCAT(
            CASE sem.semester_name WHEN 'Fall' THEN 'Fall' ELSE 'Spr' END,
            ' ',
            SUBSTRING(sem.academic_year, 3, 2),
            '/',
            SUBSTRING(sem.academic_year, 8, 2)
        ) as semester_label"),
        'sem.start_date'
    )
    ->orderBy('sem.start_date')
    ->orderBy('c.course_code');

$courseHistory = $query->get()->map(fn($row) => [
    'course_code'    => $row->course_code,
    'course_id'      => $row->course_id,
    'credit_hours'   => (int) $row->credit_hours,
    'semester_label' => $row->semester_label,
    'grade'          => $row->grade,
    'status'         => $row->status,
    'attempt_number' => (int) $row->attempt_number,
    'semester_gpa'   => (float) $row->semester_gpa,
    'risk_level'     => $row->risk_level,
]);

echo "Course History Count: " . $courseHistory->count() . "\n";
print_r($courseHistory->toArray());
