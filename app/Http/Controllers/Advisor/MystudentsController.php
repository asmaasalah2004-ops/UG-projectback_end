<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Riskevaluation;
use Illuminate\Support\Facades\DB;
use App\Models\StudentCourse;
use App\Models\Advisor;
use Illuminate\Support\Facades\Auth;

class MystudentsController extends Controller
{
     public function index(Request $request)
    {

     $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
     $advisorId = $advisor->id;

        $query = Student::with(['department'])
            ->where('advisor_id', $advisorId);
 
        
        if ($request->filled('risk_level')) {
            $query->whereHas('riskEvaluations', function ($q) use ($request) {
                $q->where('risk_level', $request->risk_level)
                  ->whereIn('id', function ($sub) {
                      $sub->selectRaw('max(id)')
                          ->from('risk_evaluation')
                          ->groupBy('student_id');
                  });
            });
        }

         
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
 
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
 
        $students = $query->get();

         
        $studentIds = $students->pluck('id');
 
        $latestRisks = RiskEvaluation::whereIn('student_id', $studentIds)
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('max(id)')
                    ->from('risk_evaluation')
                    ->groupBy('student_id');
            })
            ->get()
            ->keyBy('student_id');

        $semesterId = DB::table('semesters')
        ->where('is_active', 1)
        ->value('id');

        $studentHours = DB::table('student_courses')
           ->select('student_id', DB::raw('SUM(credit_hours) as total_hours'))
           ->whereIn('student_id', $studentIds)
           ->where('semester_id', $semesterId)
           ->groupBy('student_id')
           ->pluck('total_hours', 'student_id');
 
        $result = $students->map(function ($student) use ($latestRisks, $studentHours) {
            $risk = $latestRisks->get($student->id);
          

        
            
 
            $cumulativeGpa = $risk?->cumulative_gpa ?? 0;
            $riskLevel = $risk?->risk_level;
            if ($cumulativeGpa < 2.0 && $cumulativeGpa > 0) {
                $riskLevel = 'High level';
            } elseif ($cumulativeGpa >= 2.0 && $cumulativeGpa < 2.76) {
                $riskLevel = 'Medium level';
            } elseif ($cumulativeGpa >= 2.76) {
                $riskLevel = 'Low level';
            }

            return [
                'id'               => $student->id,
                'name'             => $student->name,
                'email'            => $student->email,
                'level'            => $student->level,
                'cumulative_gpa'   => $risk?->cumulative_gpa,
                'current_hours'    => $studentHours[$student->id] ?? 0,
                'failed_courses'   => $risk?->failed_courses,
                'risk_level'       => $riskLevel,
                'allowed_maxCH'    => $risk?->allowed_maxCH ?? 0,
            ];
        });
 
        return response()->json([
            'total'          => $result->count(),
            'need_attention' => $result->filter(function ($student) {
              return isset($student['risk_level']) 
              && stripos($student['risk_level'], 'high') !== false;
               })->count(),
            'students'       => $result->values(),
        ]);
    }


   
 
    }

