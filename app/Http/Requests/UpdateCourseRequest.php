<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
           'course_name' => 'sometimes|string|max:255',
           'course_code' => 'sometimes|string|max:255|unique:courses,course_code',
           'credit_hours' => 'sometimes|integer|min:1',
           'plan_semester' => 'sometimes|string|min:1|max:255',

          'departments' => 'sometimes|array',
          'departments.*.id' => 'sometimes|exists:departments,id|distinct',
         'departments.*.type_course' => 'required_with:departments|string|max:255',

        'prerequisite_ids' => 'sometimes|array',
        'prerequisite_ids.*' => 'exists:courses,id',
    
        ];
    }
}
