<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Studentcontroller extends Controller
{
     public function index(Request $request)
    {
        $query = Student::with(['department', 'advisor', 'riskEvaluations' => function ($q) {
            $q->latest('semester_id')->limit(1);
        }]);

        
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('id', 'like', "%{$request->search}%");
            });
        }

        
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        
        if ($request->level) {
            $query->where('level', $request->level);
        }

        if ($request->has('risk_level')) {
            $query->whereHas('riskEvaluations', function ($q) use ($request) {
                $q->where('risk_level', $request->risk_level);
                 
            });
        }

        $students = $query->get();

        return response()->json($students);
    }

    public function show($id)
    {
        $student = Student::with(['department', 'advisor'])
            ->findOrFail($id);

        return response()->json($student);
    }
     

     public function store(StoreStudentRequest $request)
     {
    
     DB::beginTransaction();

     try {
        
        $user = User::where('email', $request->email)->first();

        
        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
            ]);
        }

        
        if ($user->student) {
            return response()->json([
                'message' => 'User already has a student profile'
            ], 400);
        }


    
        $student = Student::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_id'=>$user->id,
            'national_id'=>$request->national_id,
            'department_id' => $request->department_id,
            'advisor_id' => $request->advisor_id,
            'level' => $request->level,
           
        ]);
        $student->riskEvaluations()->create([
          'semester_id' => $request->semester_id,
          'risk_level' => $request->risk_level,
          'semester_gpa' => $request->semester_gpa,
          'cumulative_gpa' => $request->cumulative_gpa,
          'failed_courses' => $request->failed_courses,
          'allowed_maxCH' => $request->allowed_maxCH,
          'passed_CH' => $request->passed_CH,
    ]);

    DB::commit();
 
      $student->load('user', 'department', 'advisor', 'riskEvaluations');
        return response()->json([
            'message' => 'Student created successfully',
            'data' => $student
            

        ]);


    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
    }
    
    
    public function update(UpdateStudentRequest $request, $id)
    {
       $student = Student::findOrFail($id);
        $student->update([
            'name' => $request->name ?? $student->name,
            'national_id'=>$request->national_id ?? $student->national_id,
            'email' => $request->email ?? $student->email,
            'password' => $request->password ? Hash::make($request->password) : $student->password,
            'department_id' => $request->department_id ?? $student->department_id,
            'advisor_id' => $request->advisor_id ?? $student->advisor_id,
            'level' => $request->level ?? $student->level,
            
        ]);

   
    if ($request->has('risk_level')) {

    
        $existingRisk = $student->riskEvaluations()
                                ->where('semester_id', $request->semester_id)
                                ->first();

        $student->riskEvaluations()->updateOrCreate(
            [
                'semester_id' => $request->semester_id,
            ],
            [
                'risk_level' => $request->risk_level  ?? $existingRisk?->risk_level  ?? 'Unknown',
                'semester_gpa' => $request->semester_gpa  ?? $existingRisk?->semester_gpa  ?? 0,
                'cumulative_gpa'=> $request->cumulative_gpa  ?? $existingRisk?->cumulative_gpa  ?? 0,
                'failed_courses'=> $request->failed_courses  ?? $existingRisk?->failed_courses  ?? 0,
                'allowed_maxCH' => $request->allowed_maxCH   ?? $existingRisk?->allowed_maxCH   ?? 0,
                'passed_CH' => $request->passed_CH  ?? $existingRisk?->passed_CH  ?? 0,
            ]
        );
    }

    return response()->json([
        'message' => 'Student updated successfully',
        'data'    => $student->with('riskEvaluations')->find($student->id)
    ]);
}
   

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }
}
