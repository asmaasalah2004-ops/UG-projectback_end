<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest
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
    	   'semester_name'=>'required|string|max:255',
    	   'is_active'=>'required|boolean',
    	   'academic_year' =>'required|string',
           'start_date'=> 'required|date',
           'end_date'=> 'required|date|after:start_date',
        ];
    }
}
