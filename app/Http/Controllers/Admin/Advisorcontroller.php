<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvisorRequest;
use App\Http\Requests\UpdateAdvisorRequest;
use App\Models\Advisor;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class Advisorcontroller extends Controller
{
    public function index(Request $request){
    $query=Advisor::query();

    if($request->search){
        $query->where('name','like',"%{$request->search}%");
    }

    if($request->department_id){
        $query->where('department_id',$request->department_id);
    }

    $advisors=$query->withCount('Students')->get();
    
    return response()->json($advisors);
    }


public function store(StoreAdvisorRequest $request){
    try {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'advisor'
        ]);
    
        $advisor = Advisor::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'department_id' => $request->department_id,
            'level' => $request->level,
            'max_student' => $request->max_student,
        ]);
        $advisor->load('user');
    
        return response()->json($advisor);
    } catch (\Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ], 500);
    }
}



    public function show($id){ 
        $advisor=Advisor::with('Students')->withCount('Students')->findorFail($id);

        return response()->json($advisor);
    }


    public function update(UpdateAdvisorRequest $request,$id){

        $advisor=Advisor::findorFail($id);
        
        $advisor->update($request->validated());
        return response()->json($advisor);
    }


    public function destroy($id){

        $advisor=Advisor::findorFail($id);
        $advisor->delete();
        return response()->json(['message'=>'Advisor deleted successfully']);
    }  
}
