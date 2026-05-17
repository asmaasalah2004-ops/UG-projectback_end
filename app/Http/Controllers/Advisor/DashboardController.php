<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        $advisor =Advisor::where('user_id', Auth::id())->first();
        $advisorId = $advisor->id;
 
        
        $activeSemester = DB::table('semesters')
            ->where('is_active', '1')
            ->first();
 
        if (!$activeSemester) {
            return response()->json([
                'message' => 'No active semester found.',
            ], 404);
        }
 
        $semesterId = $activeSemester->id;
        $today      = Carbon::today()->toDateString();

        $totalStudents = DB::table('students')
            ->where('advisor_id', $advisorId)
            ->count();

        
        $maxStudents = DB::table('advisors')
            ->where('id', $advisorId)
            ->value('max_student');

        
        $riskCounts = DB::table('risk_evaluation')
            ->join('students', 'risk_evaluation.student_id', '=', 'students.id')
            ->where('students.advisor_id', $advisorId)
            ->where('risk_evaluation.semester_id', $semesterId)
            ->select('risk_evaluation.risk_level', DB::raw('count(*) as total'))
            ->groupBy('risk_evaluation.risk_level')
            ->pluck('total', 'risk_level');

        // Make risk counts case-insensitive and handle different string formats
        $highRiskCount = 0;
        $mediumRiskCount = 0;
        foreach ($riskCounts as $level => $count) {
            $lowLevel = strtolower($level);
            if (str_contains($lowLevel, 'high')) $highRiskCount += $count;
            elseif (str_contains($lowLevel, 'medium')) $mediumRiskCount += $count;
        }

        
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd   = Carbon::now()->endOfWeek()->toDateString();


        
        $pendingAppointments = DB::table('appointments')
            ->join('time_slots', 'appointments.slot_id', '=', 'time_slots.id')
            ->join('students', 'appointments.student_id', '=', 'students.id')
            ->where('students.advisor_id', $advisorId)
            ->where('appointments.semester_id', $semesterId)
            ->whereIn('appointments.status', ['Booked', 'booked'])
            ->whereBetween('time_slots.slot_date', [$weekStart, $weekEnd])
            ->count();


            
        $studentsAtRisk = DB::table('risk_evaluation')
            ->join('students', 'risk_evaluation.student_id', '=', 'students.id')
            ->where('students.advisor_id', $advisorId)
            ->whereIn('risk_evaluation.id', function ($query) {
             $query->select(DB::raw('MAX(id)'))
             ->from('risk_evaluation')
             ->groupBy('student_id');
            })
            
            ->select(
                'students.id',
                'students.name',
                'students.level',
                'risk_evaluation.risk_level',
                'risk_evaluation.cumulative_gpa',
                'risk_evaluation.semester_gpa',
               
            )
            ->orderByRaw("
                CASE risk_evaluation.risk_level
                    WHEN 'High level'   THEN 1
                    WHEN 'Medium level' THEN 2
                    WHEN 'Low level'    THEN 3
                    ELSE 4
                END
            ")
            ->get()
            ->map(function ($student) {
                
                $nameParts = explode(' ', $student->name);
                $initials  = strtoupper(
                    substr($nameParts[0] ?? '', 0, 1) .
                    substr($nameParts[1] ?? '', 0, 1)
                );

                
               

                return [
                    'student_id'     => $student->id,
                    'name'           => $student->name,
                    'initials'       => $initials,
                    'level'          => $student->level,
                    'risk_level'     => $student->risk_level,
                    'cumulative_gpa' => $student->cumulative_gpa,
                    'semester_gpa'   => $student->semester_gpa,
                ];
            });


            $todayAppointments = DB::table('appointments')
            ->join('time_slots', 'appointments.slot_id', '=', 'time_slots.id')
            ->join('students', 'appointments.student_id', '=', 'students.id')
            
            ->leftJoin(
                DB::raw('(
                    SELECT student_id, risk_level, cumulative_gpa
                    FROM risk_evaluation
                    WHERE semester_id = ' . $semesterId . '
                ) as re'),
                'students.id', '=', 're.student_id'
            )
            ->where('students.advisor_id', $advisorId)
            ->where('appointments.semester_id', $semesterId)
            ->whereDate('time_slots.slot_date', $today)
            ->select(
                'appointments.id as appointment_id',
                'students.id as student_id',
                'students.name as student_name',
                'time_slots.slot_date',
                'time_slots.start_time',
                'time_slots.end_time',
                'appointments.status',
                're.risk_level',
                're.cumulative_gpa',
            )
            ->orderBy('time_slots.start_time')
            ->get()
            ->map(function ($appt) {
                $nameParts = explode(' ', $appt->student_name);
                $initials  = strtoupper(
                    substr($nameParts[0] ?? '', 0, 1) .
                    substr($nameParts[1] ?? '', 0, 1)
                );

                
                $dateFormatted = Carbon::parse($appt->slot_date)->format('d M');
                [$day, $month] = explode(' ', strtoupper($dateFormatted));

                return [
                    'appointment_id' => $appt->appointment_id,
                    'student_id'     => $appt->student_id,
                    'student_name'   => $appt->student_name,
                    'initials'       => $initials,
                    'day'            => $day,
                    'month'          => $month,
                    'start_time'     => $appt->start_time,
                    'end_time'       => $appt->end_time,
                    'time_range'     => $appt->start_time . ' - ' . $appt->end_time,
                    'status'         => $appt->status,
                    'risk_level'     => $appt->risk_level,
                    'cumulative_gpa' => $appt->cumulative_gpa,
                ];
            });

         $department_name= DB::table('departments')
            ->join('advisors', 'departments.id', '=', 'advisors.department_id')
            ->where('advisors.id', $advisorId)
            ->value('departments.name');

        return response()->json([
            'advisor' => [
                'advisor_id' => $advisorId,
                'name'       => $advisor->name,
                'department' => $department_name ?? null,
            ],
            'semester' => [
                'semester_id'   => $activeSemester->id,
                'academic_year' => $activeSemester->academic_year,
                'semester_name' => $activeSemester->semester_name,
            ],
            'summary' => [
                'total_students'      => $totalStudents,
                'max_students'        => $maxStudents,
                'high_risk_count'     => $highRiskCount,
                'medium_risk_count'   => $mediumRiskCount,
                'pending_appointments_this_week' => $pendingAppointments,
            ],
            'students_at_risk'   => $studentsAtRisk,
            'today_appointments' => $todayAppointments,
        ]);
    }
    }

