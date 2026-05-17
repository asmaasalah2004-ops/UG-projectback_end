<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
              'name' => 'required|string|max:255',
	          'national_id' => 'required|string|unique:students,national_id',
	          'email' => 'required|email|unique:students,email',
	          'password' => 'required|string|min:8',
	          'level' => 'required|string',
	          'department_id' => 'required|exists:departments,id',
	          'advisor_id' => 'nullable|exists:advisors,id',
              'semester_id' => 'required|exists:semesters,id',
              'risk_level' => 'required|',
              'semester_gpa' => 'required|numeric|between:0,4',
              'cumulative_gpa' => 'required|numeric|between:0,4',
              'failed_courses' => 'required|integer|min:0',
              'allowed_maxCH' => 'required|integer|min:0',
              'passed_CH' => 'required|integer|min:0'
        ];
    }
}
