<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\RiskEvaluation;
use App\Http\Controllers\Controller;

class CoursesController extends Controller
{
   public function index(Request $request){

   
       $student = Student::where('user_id', Auth::id())->first(); 
       if (!$student) {
    return response()->json(['message' => 'Student not found'], 404);
}

     $studentData = DB::table('students as s')
            ->join('departments as d', 'd.id', '=', 's.department_id')
            ->where('s.id', $student->id)
            ->select(
                's.id as id',
                's.name',
                's.level',
                'd.name as department'
            )
            ->first();


            
               $semesters = DB::table('semesters as sem')
               ->join('student_courses as sc', 'sc.semester_id', '=', 'sem.id')
               ->where('sc.student_id', $student->id)
               ->select(
                'sem.id as semester_id',
               'sem.semester_name',
               'sem.academic_year',
               'sem.start_date',
               DB::raw("CONCAT(
                    CASE sem.semester_name WHEN 'Fall' THEN 'Fall' ELSE 'Spr' END,
                    ' ',
                    SUBSTRING(sem.academic_year, 3, 2),
                    '/',
                    SUBSTRING(sem.academic_year, 8, 2)
                ) as label")
              )
             ->orderBy('sem.start_date', 'asc')
            ->get()
            ->map(fn($row) => [
                'semester_id' => $row->semester_id,
                'label'       => $row->label,
            ]);



            $semesterGpa = DB::table('risk_evaluation as re')
            ->join('semesters as sem', 'sem.id', '=', 're.semester_id')
           ->where('re.student_id', $student->id)
           ->select(
             'sem.id as semester_id',
             DB::raw("CONCAT(
            CASE sem.semester_name WHEN 'Fall' THEN 'Fall' ELSE 'Spr' END,
            ' ',
            SUBSTRING(sem.academic_year, 3, 2),
            '/',
            SUBSTRING(sem.academic_year, 8, 2)
             ) as label"),
            're.semester_gpa as gpa',
           'sem.start_date'
        )
        ->groupBy(
        'sem.id',
        'sem.semester_name',
        'sem.academic_year',
        'sem.start_date',
        're.semester_gpa'
       )
        ->orderBy('sem.start_date')
       ->get()
       ->map(fn($row) => [
        'semester_id' => $row->semester_id,
        'label'       => $row->label,
        'gpa'         => (float) $row->gpa,
    ]);
   

     $allGrades = DB::table('student_courses')
            ->where('student_id', $student->id)
            ->whereNotNull('grade')
            ->pluck('grade');
 
        $gradeDistribution = $this->calculateGradeDistribution($allGrades);


                $semesterId = $request->input('semester_id');
 
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
                'c.course_name',
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
 
        if ($semesterId) {
            $query->where('sc.semester_id', (int) $semesterId);
        }
 
        $courseHistory = $query->get()->unique(fn($row) => $row->id . '-' . $row->course_id)->values()->map(fn($row) => [
            'course_code'    => $row->course_code,
            'course_name'    => $row->course_name,
            'course_id'    => $row->course_id,
            'credit_hours'   => (int) $row->credit_hours,
            'semester_label' => $row->semester_label,
            'grade'          => $row->grade,
            'status'         => $row->status,
            'attempt_number' => (int) $row->attempt_number,
            'semester_gpa'   => (float) $row->semester_gpa,
            'risk_level'     => $row->risk_level,
        ]);

         return response()->json([
            'student'            => $studentData,
            'semester_gpa'       => $semesterGpa,
            'grade_distribution' => $gradeDistribution,
            'course_history'     => $courseHistory,
            'semesters'          => $semesters,
           
        ]);

   }

   

   private function calculateGradeDistribution($grades): array
    {
        $buckets = [
            'A+/A' => ['grades' => ['A+', 'A']],
            'B+/B' => ['grades' => ['B+', 'B']],
            'C+/C' => ['grades' => ['C+', 'C']],
            'D+/D' => ['grades' => ['D+', 'D']],
            'F'    => ['grades' => ['F']],
        ];
 
        $total = count($grades);
        if ($total === 0) return [];
 
        $result = [];
        foreach ($buckets as $label => $config) {
            $count = collect($grades)
                ->filter(fn($g) => in_array(strtoupper(trim($g)), $config['grades']))
                ->count();
 
            if ($count > 0) {
                $result[] = [
                    'label'      => $label,
                    'count'      => $count,
                    'percentage' => round(($count / $total) * 100),
                  
                ];
            }
        }
 
        return $result;
    }
 
   
     
   
}
