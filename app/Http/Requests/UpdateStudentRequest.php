<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
                'name' => 'sometimes|string|max:255',
                'national_id' => 'sometimes|string|unique:students,national_id,' . $this->route('id'),
                'email' => 'sometimes|email|unique:students,email,' . $this->route('id'),
                'password' => 'sometimes|string|min:8',
                'level' => 'sometimes|string',
                'department_id' => 'sometimes|exists:departments,id',
                'advisor_id' => 'nullable|exists:advisors,id',
                'semester_id' => 'sometimes|exists:semesters,id',
                'risk_level' => 'sometimes',
                'semester_gpa' => 'sometimes|numeric|between:0,4',
                'cumulative_gpa' => 'sometimes|numeric|between:0,4',
                'failed_courses' => 'sometimes|integer|min:0',
                'allowed_maxCH' => 'sometimes|integer|min:0',
                'passed_CH' => 'sometimes|integer|min:0'
        ];
    }
}
