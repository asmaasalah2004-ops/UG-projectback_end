<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Advisor;
use App\Models\Course;
use App\Models\Courseprerequisite;
use App\Models\Riskevaluation;
use App\Models\Appointment;
use App\Models\Department;

class Dashboardcontroller extends Controller
{
    public function index(){

         $totalStudents = Student::count();

        $activeAdvisors = Advisor::count();

        $totalCourses = Course::count();

        $coursesWithPrereqs = Course::whereHas('prerequisites')->count();
        
        $atRiskStudents = Student::whereHas('riskEvaluations', function ($q) {
            $q->whereIn('id', function($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('risk_evaluation')
                    ->groupBy('student_id');
            })->whereRaw('LOWER(risk_level) LIKE ?', ['%high%']);
        })->count();

        // Latest risk distribution
        $latestEvaluations = Riskevaluation::whereIn('id', function($query) {
            $query->selectRaw('MAX(id)')
                  ->from('risk_evaluation')
                  ->groupBy('student_id');
        })->get();

        $riskDistribution = [
            'high'   => $latestEvaluations->filter(fn($e) => str_contains(strtolower($e->risk_level), 'high'))->count(),
            'medium' => $latestEvaluations->filter(fn($e) => str_contains(strtolower($e->risk_level), 'medium'))->count(),
            'low'    => $latestEvaluations->filter(fn($e) => str_contains(strtolower($e->risk_level), 'low'))->count(),
        ];

        $appointments = Appointment::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        ")->first();

        $departments = Department::withCount([
          'students',
         'advisors',
          'appointments'
        ])->get()
    ->map(function ($dept) {

        $dept->high_risk = Student::where('department_id', $dept->id)
            ->whereHas('riskEvaluations', function ($q) {
                $q->where('risk_level', 'high');
            })
            ->count();

     
        $dept->avg_gpa = RiskEvaluation::whereHas('student', function ($q) use ($dept) {
            $q->where('department_id', $dept->id);
        })->avg('cumulative_gpa');

        return $dept;

    });
     return response()->json([
            'overview' => [
                'total_students' => $totalStudents,
                'active_advisors' => $activeAdvisors,
                'total_courses' => $totalCourses,
                'courses_with_prereqs' => $coursesWithPrereqs,
                 'at_risk_students' => $atRiskStudents,
            ],

             'risk_distribution' => $riskDistribution,

            'appointments' => [
                'total' => $appointments->total ?? 0,
                'attended' => $appointments->attended ?? 0,
                'booked' => $appointments->booked ?? 0,
                'cancelled' => $appointments->cancelled ?? 0,
            ],

            'departments' => $departments,
        ]);
    }

}
