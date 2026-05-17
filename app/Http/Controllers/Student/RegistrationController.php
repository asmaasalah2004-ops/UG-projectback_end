<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Validator;
use App\Models\RiskEvaluation;
use App\Models\Semester;
use App\Models\Appointment;




class RegistrationController extends Controller
{
    public function index(Request $request){

    $student = Student::where('user_id', Auth::id())->first();

    if (! $student) {
    return response()->json([
        'message' => 'Student not found for this user'
    ], 404);
}

$studentId = $student->id;

        $departmentId = $student->department_id;
 
        
        $semester = DB::table('semesters')
            ->where('is_active', '1')
            ->first();
 
        if (! $semester) {
            return response()->json(['message' => 'No active semester found.'], 404);
        }
        

        $passedCourses = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('status', 'Passed')
            ->get();
 
        $chPassed   = $passedCourses->sum('credit_hours');
        $passedIds  = $passedCourses->pluck('courses_id')->toArray();
 

        $historicalCourses = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->get()
            ->keyBy('courses_id');
 
        
        $riskEval = DB::table('risk_evaluation')
            ->where('student_id', $studentId)
            ->orderByDesc('semester_id')
            ->first();
 
        $cumGpa        = $riskEval?->cumulative_gpa ?? 0.00;
        $failedCount   = $riskEval?->failed_courses ?? 0;
        $maxAllowedCh  = (int) ($riskEval?->allowed_maxCH ?? 18);
        if ($maxAllowedCh < 12) $maxAllowedCh = 12;
       
        $hasConfirmed = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->whereIn('status', ['Confirmed', 'Registered'])
            ->exists();
      
         $currentEnrollments = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->join('courses', 'student_courses.courses_id', '=', 'courses.id')
            ->select(
                'student_courses.id as enrollment_id',
                'student_courses.courses_id',
                'courses.course_code',
                'courses.course_name',
                'courses.credit_hours',
                'student_courses.status'
            )
            ->get();
 
        $enrolledCh = $currentEnrollments->sum('credit_hours');
        

         $departmentName = DB::table('departments')->where('id', $departmentId)->value('name');
         $prefixes = $this->getPrefixesForDepartment($departmentName);
         $generalPrefixes = ['ETHR', 'SKIL', 'HURI', 'SAFS', 'ENGL', 'INCO', 'SCTH'];
         $allPrefixes = array_merge($prefixes, $generalPrefixes);

         $departmentCourses = DB::table('courses')
            ->where(function($query) use ($allPrefixes) {
                foreach ($allPrefixes as $prefix) {
                    $query->orWhere('course_code', 'LIKE', $prefix . ' %');
                }
            })
            ->select(
                'courses.id',
                'courses.course_code',
                'courses.course_name',
                'courses.credit_hours',
                'courses.plan_semester'
            )
            ->get();

                    $prereqMap = DB::table('course_prerequisites')
            ->get()
            ->groupBy('course_id')
            ->map(fn($rows) => $rows->pluck('prerequisite_id')->toArray());
 
        
        $enrolledIds = $currentEnrollments->pluck('courses_id')->toArray();
 
        $available = $departmentCourses
            ->filter(function ($course) use ($passedIds, $enrolledIds) {
                
                return ! in_array($course->id, $passedIds)
                    && ! in_array($course->id, $enrolledIds);
            })
            ->map(function ($course) use ($prereqMap, $passedIds, $historicalCourses) {
                $prereqs     = $prereqMap[$course->id] ?? [];
                $metPrereqs  = array_intersect($prereqs, $passedIds);
                $missedPreqs = array_diff($prereqs, $passedIds);
 
               
                $prevRecord     = $historicalCourses[$course->id] ?? null;
                $attemptNumber  = $prevRecord ? ($prevRecord->attempt_number + 1) : 1;
                
                $type = $attemptNumber === 1 ? 'New' : 'Retake';
 
               
                $prereqLabels = [];
                if (! empty($prereqs)) {
                    $prereqCourses = DB::table('courses')
                        ->whereIn('id', $prereqs)
                        ->pluck('course_code', 'id');
 
                    foreach ($prereqs as $pid) {
                        $prereqLabels[] = [
                            'course_id'   => $pid,
                            'course_code' => $prereqCourses[$pid] ?? $pid,
                            'met'         => in_array($pid, $passedIds),
                        ];
                    }
                }
 
                $canEnroll = empty($missedPreqs);
 
                return [
                    'course_id'      => $course->id,
                    'course_code'    => $course->course_code,
                    'course_name'    => $course->course_name,
                    'credit_hour'    => $course->credit_hours,
                    'plan_semester'  => $course->plan_semester,
                    'type'           => $type,          
                    'prerequisites'  => $prereqLabels,
                    'can_enroll'     => $canEnroll,
                ];
            })
            ->values();
 
        return response()->json([
            'semester' => [
                'semester_id'   => $semester->id,
                'academic_year' => $semester->academic_year,
                'semester_name' => $semester->semester_name,
                'end_date'      => $semester->end_date,
            ],
            'student' => [
                'student_id'     => $studentId,
                'name'           => $student->name,
                'level'          => $student->level,
                'department_id'  => $departmentId,
            ],
            'stats' => [
                'failed_courses'  => (int) $failedCount,
                'ch_passed'       => (int) $chPassed,
                'enrolled_ch'     => (int) $enrolledCh,
                'cumulative_gpa'  => round((float) $cumGpa, 2),
                'max_allowed_ch'  => (int) $maxAllowedCh,
                'academic_warning'=> $cumGpa,
                'registration_confirmed' => $hasConfirmed,
            ],
            'current_enrollments' => $currentEnrollments,
            'available_courses'   => $available,
        ]);
    }

    public function enroll(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'course_id' => 'required|integer|exists:courses,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $student = Student::where('user_id', Auth::id())->first(); 
    if (!$student) {
        return response()->json(['message' => 'Student record not found.'], 404);
    }

    $studentId    = $student->id;
    $departmentId = $student->department_id;
    $courseId     = (int) $request->course_id;

    try {
        $result = DB::transaction(function () use ($studentId, $departmentId, $courseId) {
            $semester = DB::table('semesters')->where('is_active', '1')->first();
            if (! $semester) {
                return response()->json(['message' => 'Registration is closed (no active semester).'], 403);
            }

          
            $alreadyEnrolled = DB::table('student_courses')
                ->where('student_id', $studentId)
                ->where('courses_id', $courseId)
                ->where('semester_id', $semester->id)
                ->exists();

            if ($alreadyEnrolled) {
                return response()->json(['message' => 'You are already enrolled in this course for the current semester.'], 409);
            }

            $departmentName = DB::table('departments')->where('id', $departmentId)->value('name');
            $prefixes = $this->getPrefixesForDepartment($departmentName);
            $generalPrefixes = ['ETHR', 'SKIL', 'HURI', 'SAFS', 'ENGL', 'INCO', 'SCTH'];
            $allPrefixes = array_merge($prefixes, $generalPrefixes);

            $courseCode = DB::table('courses')->where('id', $courseId)->value('course_code');
            $coursePrefix = explode(' ', $courseCode)[0] ?? '';

            $isBelongs = in_array($coursePrefix, $allPrefixes);

            if (! $isBelongs) {
                return response()->json(['message' => 'Course does not belong to your department.'], 403);
            }

            $alreadyPassed = DB::table('student_courses')
                ->where('student_id', $studentId)
                ->where('courses_id', $courseId)
                ->where('status', 'Passed')
                ->exists();

            if ($alreadyPassed) {
               return response()->json(['message' => 'You have already passed this course.'], 409);
            }

            $prereqs = DB::table('course_prerequisites')
                ->where('course_id', $courseId)
                ->pluck('prerequisite_id')
                ->toArray();

            if (! empty($prereqs)) {
                $passedIds = DB::table('student_courses')
                    ->where('student_id', $studentId)
                    ->where('status', 'Passed')
                    ->pluck('courses_id')
                    ->toArray();

                $missing = array_diff($prereqs, $passedIds);
                if (! empty($missing)) {
                    $missingCodes = DB::table('courses')
                        ->whereIn('id', $missing)
                        ->pluck('course_code')
                        ->toArray();
                    return response()->json([
                        'message' => 'Prerequisites not met.',
                        'missing' => $missingCodes
                    ], 422);
                }
            }

            
            $riskEval = DB::table('risk_evaluation')
                ->where('student_id', $studentId)
                ->orderByDesc('semester_id')
                ->first();

            $maxAllowedCh  = (int) ($riskEval?->allowed_maxCH ?? 18);
            if ($maxAllowedCh < 12) $maxAllowedCh = 12;

            $enrolledCh = DB::table('student_courses')
                ->where('student_id', $studentId)
                ->where('semester_id', $semester->id)
                ->join('courses', 'student_courses.courses_id', '=', 'courses.id')
                ->sum('courses.credit_hours');

            $course = DB::table('courses')->where('id', $courseId)->first();
            if (!$course) {
                return response()->json(['message' => 'Course not found.'], 404);
            }

            if (($enrolledCh + $course->credit_hours) > $maxAllowedCh) {
                return response()->json([
                    'message' => "Adding this course exceeds your max allowed credit hours ({$maxAllowedCh} CH).",
                ], 403);
            }

            
            $lastAttempt = DB::table('student_courses')
                ->where('student_id', $studentId)
                ->where('courses_id', $courseId)
                ->max('attempt_number');

            $attemptNumber = $lastAttempt ? $lastAttempt + 1 : 1;


            
            $id = DB::table('student_courses')->insertGetId([
                'student_id'          => $studentId,
                'courses_id'          => $courseId,
                'semester_id'         => $semester->id,
                'credit_hours'        => $course->credit_hours,
                'grade'               => null,
                'status'              => null,
                'attempt_number'      => $attemptNumber,
                
              
                
                
            ]);

            $newEnrolledCh = $enrolledCh + $course->credit_hours;

            return response()->json([
                'message' => 'Enrolled successfully.',
                'enrollment_id' => $id,
                'course' => [
                    'course_id' => $course->id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'credit_hour' => $course->credit_hours,
                    'attempt_number' => $attemptNumber,
                    'type' => $attemptNumber === 1 ? 'New' : 'Retake',
                ],
                'stats' => [
                    'enrolled_ch' => (int) $newEnrolledCh,
                    'max_allowed_ch' => (int) $maxAllowedCh,
                ],
            ], 201);
        });

        return $result;

    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Something went wrong.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

 public function unenroll(Request $request, int $enrollmentId)
{
    try {

        return DB::transaction(function () use ($request, $enrollmentId) {

            $student = Student::where('user_id', Auth::id())->first(); 
            $studentId = $student->id;

            $semester = DB::table('semesters')->where('is_active', '1')->first();
            if (! $semester) {
                throw new \Exception('Registration is closed.');
            }

            $enrollment = DB::table('student_courses')
                ->where('id', $enrollmentId)
                ->where('student_id', $studentId)
                ->where('semester_id', $semester->id)
                ->first();

            if (! $enrollment) {
              throw new \Exception('Enrollment record not found for this course.');
            }

            $course = DB::table('courses')
                ->where('id', $enrollment->courses_id)
                ->first();

            DB::table('student_courses')
                ->where('id', $enrollmentId)
                ->delete();

            $enrolledCh = DB::table('student_courses')
                ->where('student_id', $studentId)
                ->where('semester_id', $semester->id)
                ->join('courses', 'student_courses.courses_id', '=', 'courses.id')
                ->sum('courses.credit_hours');

            $riskEval = DB::table('risk_evaluation')
                ->where('student_id', $studentId)
                ->orderByDesc('semester_id')
                ->first();

            $maxAllowedCh  = (int) ($riskEval?->allowed_maxCH ?? 18);
            if ($maxAllowedCh < 12) $maxAllowedCh = 12;

            return response()->json([
                'message' => 'Course removed from your selection.',
                'removed' => [
                    'enrollment_id' => $enrollmentId,
                    'course_id'     => $enrollment->courses_id,
                    'course_code'   => $course->course_code,
                    'course_name'   => $course->course_name,
                    'credit_hours'  => $course->credit_hours,
                ],
                'stats' => [
                    'enrolled_ch'    => (int) $enrolledCh,
                    'max_allowed_ch' => (int) $maxAllowedCh,
                ],
            ]);

        });

    } catch (\Throwable $e) {

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}

    public function confirm(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first();
        $studentId = $student->id;
 
        $semester = DB::table('semesters')->where('is_active', '1')->first();
        if (! $semester) {
            return response()->json(['message' => 'Registration is closed.'], 403);
        }
 
        $enrollments = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->get();
 
        if ($enrollments->isEmpty()) {
            return response()->json(['message' => 'No courses selected to confirm.'], 422);
        }
 
        $enrolledCh = DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->join('courses', 'student_courses.courses_id', '=', 'courses.id')
            ->sum('courses.credit_hours');
 
        // Update status of all enrolled courses for this active semester to 'Confirmed'
        DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->whereNull('status')
            ->update(['status' => 'Confirmed']);
 
        return response()->json([
            'message'       => 'Registration confirmed successfully.',
            'semester_id'   => $semester->id,
            'courses_count' => $enrollments->count(),
            'total_ch'      => (int) $enrolledCh,
        ]);
    }

    public function unconfirm(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first();
        $studentId = $student->id;
 
        $semester = DB::table('semesters')->where('is_active', '1')->first();
        if (! $semester) {
            return response()->json(['message' => 'Registration is closed.'], 403);
        }
 
        // Update status of all enrolled courses for this active semester back to null
        DB::table('student_courses')
            ->where('student_id', $studentId)
            ->where('semester_id', $semester->id)
            ->where('status', 'Confirmed')
            ->update(['status' => null]);
 
        return response()->json([
            'message' => 'Registration unconfirmed successfully.',
        ]);
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
