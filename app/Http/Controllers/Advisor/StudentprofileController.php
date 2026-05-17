<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\RiskEvaluation;
use Illuminate\Support\Facades\DB;
use App\Models\Studentcourse;
use App\Models\Appointment;
use App\Http\Requests\AddCourseRequest;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use App\Models\Advisor;





class StudentprofileController extends Controller
{
     public function index($studentId)
    {
        
      $advisor =Advisor::where('user_id', Auth::id())->first();
        $advisorId = $advisor->id;

        $student = Student::with('department')
            ->where('advisor_id', $advisorId)
            ->where('id', $studentId)
            ->firstOrFail();
 
        $latestRisk = RiskEvaluation::where('student_id', $studentId)
            ->with('semester')
            ->latest('id')
            ->first();

            $department_name= DB::table('departments')
            ->join('advisors', 'departments.id', '=', 'advisors.department_id')
            ->where('advisors.id', $advisorId)
            ->value('departments.name');

 
        return response()->json([
            'header'           => $this->header($student, $latestRisk, $department_name),
            'summary_cards'    => $this->summaryCards($studentId, $latestRisk),
            'current_courses'  => $this->currentCourses($studentId),
            'gpa_history'      => $this->gpaHistory($studentId),
            'academic_history' => $this->academicHistory($studentId),
            'student_notes'    => $this->studentNotes($studentId),
        ]);
    }


    private function header($student, $latestRisk, $department_name): array
    {
        return [
            'student_id'     => $student->id,
            'name'           => $student->name,
            'email'          => $student->email,
            'initials'       => $this->initials($student->name),
            'level'          => $student->level,
            'department'     => $department_name ?? null,
            'risk_level'     => $latestRisk?->risk_level,
            'cumulative_gpa' => $latestRisk?->cumulative_gpa,
        ];
    }

     private function summaryCards($studentId, $latestRisk): array
    {
        $totalPassed = Studentcourse::where('student_id', $studentId)
            ->where('status', 'Passed')
            ->sum('credit_hours');
        
        $semesterId = DB::table('semesters')
            ->where('is_active', 1)
            ->value('id');


         $studentHours = DB::table('student_courses')
           ->select('student_id', DB::raw('SUM(credit_hours) as total_hours'))
           ->where('student_id', $studentId)
           ->where('semester_id', $semesterId)
           ->groupBy('student_id')
           ->pluck('total_hours', 'student_id');


        return [
            'failed_courses'       => $latestRisk?->failed_courses ?? 0,
            'current_hours'    => $studentHours[$studentId] ?? 0,
            'total_ch_passed'      => $totalPassed,
        ];
    }



     private function currentCourses($studentId): array
    {
        
        $semesterId = DB::table('semesters')
            ->where('is_active', 1)
            ->value('id');


        $currentEnrollments = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->join('courses', 'student_courses.courses_id', '=', 'courses.id')
            ->leftJoin('course_prerequisites', 'courses.id', '=', 'course_prerequisites.course_id')
            ->leftJoin('courses as prereq_courses', 'course_prerequisites.prerequisite_id', '=', 'prereq_courses.id')
            ->select(
                'student_courses.courses_id',
                'courses.course_code',
                'courses.course_name',
                'courses.credit_hours', 
                'prereq_courses.course_name as prerequisite_name',
                'student_courses.attempt_number'
            )
            ->get();

        $totalCH = $currentEnrollments->groupBy('courses_id')
            ->map(fn($items) => $items->first()->credit_hours)
            ->sum();

        $courses = $currentEnrollments->groupBy('courses_id')->map(function ($items) {

        $first = $items->first();

        return [
            'course_id'      => $first->courses_id,
            'course_code'    => $first->course_code,
            'course_name'    => $first->course_name,
            'credit_hours'   => $first->credit_hours,
            'prerequisites'  => $items->pluck('prerequisite_name')->filter()->values(),
            'attempt_number' => $first->attempt_number,
        ];
    });

 
        return [
            'total_enrolled_ch' => $totalCH,
            'courses'           => $courses->values(),
        ];
    }

     private function gpaHistory($studentId): array
    {
        $history = RiskEvaluation::where('student_id', $studentId)
            ->with('semester')
            ->orderBy('semester_id')
            ->get()
            ->map(fn ($item) => [
                
                
                'semester_gpa' => $item->semester_gpa ?? 0,
                'semester_name'=> $item->semester?->semester_name . ' ' . $item->semester?->academic_year,
            ]);
 
        return $history->values()->toArray();
       
    }

     private function academicHistory($studentId): array
    {
        $records = Studentcourse::with(['semester', 'course'])
            ->where('student_id', $studentId)
            ->orderByDesc('student_courses.semester_id')
            ->get()
           ->map(function ($rec) {
            return [
                'course_code' => $rec->course?->course_code,
                'course_name' => $rec->course?->course_name,
                'grade'       => $rec->grade,

                'semester' => $rec->semester
                    ? $rec->semester->semester_name . ' ' . $rec->semester->academic_year
                    : null,

                'status' => $rec->status,
            ];
        });
            
 
        return $records->values()->toArray();
    }

    private function studentNotes($studentId): array
    {
        $appointments = Appointment::with(['timeslot'])
            ->where('student_id', $studentId)
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->orderByDesc('booking_date')
            ->get()
            ->map(fn ($apt) => [
                'note'         => $apt->notes,
                'booking_date' => $apt->booking_date,
                'start_time'   => $apt->timeslot?->start_time,
                'end_time'     => $apt->timeslot?->end_time,
                'slot_date'    => $apt->timeslot?->slot_date,
            ]);
 
        return $appointments->values()->toArray();
    }

    public function availableCourses($studentId)
    {
        $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();

        $student = Student::where('advisor_id', $advisor->id)
            ->where('id', $studentId)
            ->firstOrFail();

        $semesterId = DB::table('semesters')
            ->where('is_active', 1)
            ->value('id');

        $registeredCourses = Studentcourse::where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->pluck('courses_id');

        $departmentName = DB::table('departments')->where('id', $student->department_id)->value('name');
        $prefixes = $this->getPrefixesForDepartment($departmentName);
        $generalPrefixes = ['ETHR', 'SKIL', 'HURI', 'SAFS', 'ENGL', 'INCO', 'SCTH'];
        $allPrefixes = array_merge($prefixes, $generalPrefixes);

        $courses = Course::with('prerequisites')
            ->where(function($query) use ($allPrefixes) {
                foreach ($allPrefixes as $prefix) {
                    $query->orWhere('course_code', 'LIKE', $prefix . ' %');
                }
            })
            ->whereNotIn('id', $registeredCourses)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'credit_hours' => $course->credit_hours,
                    'prerequisites' => $course->prerequisites->pluck('course_code'),
                    'can_add' => true,
                ];
            });

         return response()->json([
            'courses' => $courses
        ]);
    }


    public function addCourse(AddCourseRequest $request, $studentId)
    {
        $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
        
        $student = Student::where('advisor_id', $advisor->id)
            ->where('id', $studentId)
            ->firstOrFail();

        $semesterId = DB::table('semesters')
            ->where('is_active', 1)
            ->value('id');

        $course = Course::findOrFail($request->courses_id);

        $exists = Studentcourse::where('student_id', $studentId)
            ->where('courses_id', $request->courses_id)
            ->where('semester_id', $semesterId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Course already added'], 422);
        }

        $lastAttempt = Studentcourse::where('student_id', $studentId)
            ->where('courses_id', $request->courses_id)
            ->max('attempt_number');

        $attemptNumber = $lastAttempt ? $lastAttempt + 1 : 1;

        Studentcourse::create([
            'student_id'     => $studentId,
            'courses_id'     => $request->courses_id,
            'semester_id'    => $semesterId,
            'credit_hours'   => $course->credit_hours,
            'attempt_number' => $attemptNumber,
            'status'         => 'enrolled',
            'grade'          => null,
        ]);

        return response()->json(['message' => 'Course added successfully']);
    }



    public function removeCourse($studentId, $courseId)
    {
            $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
            $advisorId = $advisor->id;

        Student::where('advisor_id', $advisorId)
               ->where('id', $studentId)
               ->firstOrFail();
 
        $semesterId = DB::table('semesters')
            ->where('is_active', 1)
            ->value('id');

        Studentcourse::where('student_id', $studentId)
                     ->where('courses_id', $courseId)
                     ->where('semester_id', $semesterId)
                     ->firstOrFail()
                     ->delete();
 
        return response()->json(['message' => 'course removed']);
    }

     public function confirmCourses($studentId)
    {
        
    $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
     $advisorId = $advisor->id;

        Student::where('advisor_id', $advisorId)
               ->where('id', $studentId)
               ->firstOrFail();
 
        $updated = Studentcourse::where('student_id', $studentId)
                                ->where(function($q) {
                                    $q->whereNull('status')
                                      ->orWhere('status', 'Confirmed');
                                })
                                ->update(['status' => 'Registered']);
 
        return response()->json([
            'message'          => 'courses confirmed',
            'confirmed_courses'=> $updated,
        ]);
    }

    private function initials(string $name): string
    {
        $words = explode(' ', trim($name));
        return strtoupper(
            collect($words)->take(2)->map(fn ($w) => $w[0])->implode('')
        );
    }

    private function getPrefixesForDepartment($deptName) {
        switch ($deptName) {
            case 'Mathematics':
                return ['MATH', 'STAT'];
            case 'Mathematical Statistics':
                return ['STAT', 'MATH'];
            case 'Computer Sciences':
                return ['COMP', 'MATH'];
            case 'Physics':
                return ['PHYS', 'MATH'];
            case 'Biophysics':
                return ['BIOP', 'PHYS', 'MATH'];
            case 'Chemistry':
                return ['CHEM', 'MATH'];
            case 'Applied Chemistry':
                return ['CHEM', 'APCH', 'MATH'];
            case 'Pure Mathematics & Computer Science':
                return ['MATH', 'COMP'];
            case 'Mathematical Statistics & Computer Science':
                return ['STAT', 'COMP', 'MATH'];
            case 'Pure Mathematics & Mathematical Statistics':
                return ['MATH', 'STAT'];
            case 'Physics & Chemistry':
                return ['PHYS', 'CHEM', 'MATH'];
            case 'Physics & Computer Science':
                return ['PHYS', 'COMP', 'MATH'];
            default:
                return [];
        }
    }
 
 




 
}
