<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseDepartment;
use Illuminate\Http\Request;
use App\Models\Courseprerequisite;
use Illuminate\Support\Facades\DB;
use App\Models\Department;

class Coursecontroller extends Controller
{
    public function index(Request $request){
       
        $query = Course::query();
        
        if($request->search){
            $query->where(function ($q) use ($request) {
                $q->where('course_name', 'like', "%{$request->search}%")
                  ->orWhere('course_code', 'like', "%{$request->search}%");
         });
        }

       if ($request->has('department_id')) {
              $query->whereHas('courseDepartments', function ($q) use ($request) {
              $q->where('department_id', $request->department_id);
              });
       }
        

            if ($request->plan_semester) {
            $query->where('plan_semester', $request->plan_semester);
            }

            
        $courses = $query->with([
            'courseDepartments.department',
            'prerequisites'
        ])->get();

        return response()->json($courses);
            
             
    }

   public function store(StoreCourseRequest $request){
      DB::beginTransaction();
        try{
             $course=Course::create($request->validated());
       if ($request->filled('departments')) {
       foreach ($request->departments as $dep) {
 
        $department = Department::where('name', 'LIKE', '%' . $dep['department_name'] . '%')->first();
 
        if (!$department) {
          throw new \Exception("Department not found: {$dep['department_name']}");
        }
 
        CourseDepartment::create([
            'course_id'     => $course->id,
            'department_id' => $department->id,
            'type_course'   => $dep['type_course'],
        ]);
       }
    }

    if ($request->filled('prerequisites')) {
        foreach ($request->prerequisites as $prereq) {

           
            $prerequisiteCourse = null;

            if (!empty($prereq['course_code'])) {
                $prerequisiteCourse = Course::where('course_code', $prereq['course_code'])->first();
            }

         if (!$prerequisiteCourse) {
                 $identifier = $prereq['course_code'] ?? 'unknown';
                    throw new \Exception("Prerequisite course not found: {$identifier}");
            }

          
            if ($prerequisiteCourse->id === $course->id) {
               throw new \Exception("A course cannot be a prerequisite of itself.");
            }

            Courseprerequisite::create([
                'course_id'       => $course->id,
                'prerequisite_id' => $prerequisiteCourse->id,
            ]);
        }
    }
        DB::commit();
           $course->load('courseDepartments.department', 'prerequisites');

       return response()->json([
        'status' => 'success',
        'data'   => $course,
      ], 201);
    }
            
    
        catch (\Exception $e) {
            DB::rollBack();
           return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ], 422);
        }

 }
   
    
   
    public function show($id){
          $course = Course::with([
            'courseDepartments.department',
            'prerequisites'
        ])->findOrFail($id);

        return response()->json($course);
    }



    public function update(UpdateCourseRequest $request,$id){

    $course=Course::findOrFail($id);
    $course->update($request->validated());

    if($request->departments){
      CourseDepartment::where('course_id', $id)->delete();
        foreach ($request->departments as $dep) {
            CourseDepartment::create([
                'course_id' => $course->id,
                'department_id' => $dep['id'],
                'type_course' => $dep['type_course'],
            ]);
        }

    }
    
    if($request->prerequisite_ids){
        Courseprerequisite::where('course_id', $id)->delete();
            foreach ($request->prerequisite_ids as $prereq_id) {
                Courseprerequisite::create([
                    'course_id' => $course->id,
                    'prerequisite_id' => $prereq_id,
                ]);
            }
    }
    return response()->json($course::with('courseDepartments.department', 'prerequisites')->findOrFail($id));
    }



    public function destroy($id){
           $course = Course::findOrFail($id);

        CourseDepartment::where('course_id', $course->id)->delete();
        Courseprerequisite::where('course_id', $course->id)->delete();

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
      
}
}








