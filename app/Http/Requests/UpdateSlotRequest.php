<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_time'   => ['sometimes', 'date_format:H:i'],
            'end_time'     => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'max_students' => ['sometimes', 'integer', 'max:20'],
        
        ];
    }
}
