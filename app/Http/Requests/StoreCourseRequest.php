<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
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
           'course_name' => 'required|string|max:255',
           'course_code' => 'required|string|max:255|unique:courses,course_code',
           'credit_hours' => 'required|integer|min:1',
           'plan_semester' => 'required|string|min:1|max:255',

          'departments' => ['required', 'array'],
          'departments.*.department_name' => ['required', 'string'],
          'departments.*.type_course'   => ['required', 'string'],

          'course_id'=>'nullable|exists:courses,id',
          'prerequisites'               => ['nullable', 'array'],
          'prerequisites.*.course_code' => ['nullable', 'string'],
         
        ];
    }
}
