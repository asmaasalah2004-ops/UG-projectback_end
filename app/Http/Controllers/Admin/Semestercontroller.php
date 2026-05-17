<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Models\Semester;
use Illuminate\Http\Request;

class Semestercontroller extends Controller
{
    public function index()
    {
        $semesters = Semester::orderBy('academic_year', 'desc')
            ->orderBy('start_date', 'desc')
            ->get();
            

        return response()->json($semesters);
    }

    public function store(StoreSemesterRequest $request)
    {
       

        $semester = Semester::create([
            'academic_year' => $request->academic_year,
            'semester_name' => $request->semester_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active ?? '0',
        ]);

        return response()->json([
            'message'=>'Semester created successfully',
            'data'=>$semester
        ]);
    }

    public function update(UpdateSemesterRequest $request, $id)
    {
        $semester = Semester::findOrFail($id);
        $semester->update([
        'semester_name' => $request->semester_name ?? $semester->semester_name,
        'is_active'=> $request->is_active ?? $semester->is_active,
        'academic_year' => $request->academic_year ?? $semester->academic_year,
        'start_date' => $request->start_date ?? $semester->start_date,
         'end_date' => $request->end_date ?? $semester->end_date,
    ]);
        

        return response()->json([
            'message' => 'Semester updated successfully',
            'data' => $semester
        ]);
    }

     public function activate($id)
    {
        $semester = Semester::findOrFail($id);
        Semester::where('is_active', '1')->update(['is_active' => '0']);

        
        $semester->update(['is_active' => '1']);

        return response()->json([
            'message' => 'Semester activated successfully',
            'data' => $semester
        ]);
    }


}
