<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request  $request)
    {
      return response()->json(Department::all());
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        return response()->json($department);
    }

    public function destroy($id)
    {
      $department = Department::findOrFail($id);
      $department->delete();

    return response()->json(['message' => 'Deleted successfully']);
    }
}
