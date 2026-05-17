<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
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
           'semester_name'=>'sometimes|string|max:255',
    	   'is_active'=>'sometimes|boolean',
    	   'academic_year' =>'sometimes|string',
           'start_date'=> 'sometimes|date',
           'end_date'=> 'sometimes|date|after:start_date',
        ];
    }
}
