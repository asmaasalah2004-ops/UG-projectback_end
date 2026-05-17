<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RiskEvaluation;
use App\Models\Appointment;
use App\Models\Semester;
use App\Models\Student;


class Dashboardcontroller extends Controller
{
     public function index(Request $request)
    {
       $student = Student::with(['department', 'advisor.department'])
            ->where('user_id', Auth::id())
            ->first(); 

        
        $latestRisk = RiskEvaluation::where('student_id', $student->id)
            ->latest('semester_id')
            ->first();

       
        $chPassed = $latestRisk->passed_CH ?? 0;

      
        $failedCourses = $latestRisk->failed_courses ?? 0;

        
        $cumulativeGPA = $latestRisk->cumulative_gpa ?? 0;

         $department = $student->department->name ?? 'N/A';

         $currentSemester = Semester::latest()->first();
         $term = $currentSemester->semester_name ?? '';
         $academicYear = $currentSemester->academic_year ?? '';

         $riskLevel = $latestRisk->risk_level ?? 'low';

        $semesterDisplay = trim("$term $academicYear");
        $upcomingAppointment = Appointment::where('student_id', $student->id)
            ->where('booking_date', '>=', now())
            ->orderBy('booking_date', 'asc')
            ->first();

      
        $gpaProgression = RiskEvaluation::where('student_id', $student->id)
            ->with('semester')
            ->orderBy('semester_id')
            ->get()
            ->map(function ($item) {
                return [
                    'semester' => $item->semester->semester_name ?? 'N/A',
                    'gpa' => $item->semester_gpa ?? 0,
                ];
            });

        return response()->json([
            'student' => [
                'name' => $student->name,
                'level' => $student->level,
                'department' => $department,
            ],

            'advisor' => [
                'name' => $student->advisor->name ?? 'N/A',
                'department' => $student->advisor->department->name ?? 'N/A',
            ],

            'risk_level' => $riskLevel,

            'semester_display' => $semesterDisplay,

             'stats' => $latestRisk ? [
             'cumulative_gpa' => $cumulativeGPA,
             'ch_passed' => $chPassed,
             
             'failed_courses' => $failedCourses,
        ] : null,
             'upcoming_appointment' => $upcomingAppointment,

           'gpa_progression' => $gpaProgression,
        
          'message' => $latestRisk ? null : 'No risk evaluation data found'
        ]);
    }
}

