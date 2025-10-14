<?php

namespace App\Http\Requests\Headcount;

use Illuminate\Foundation\Http\FormRequest;

class CreateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Audit Leaders can create sessions
        return auth()->check() && auth()->user()->hasRole('Audit Leader');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_name' => [
                'required',
                'string',
                'max:255',
                'unique:headcount_sessions,session_name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'session_name.required' => 'Session name is required.',
            'session_name.unique' => 'A session with this name already exists. Please choose a different name.',
            'session_name.max' => 'Session name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Please provide a valid start date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.date' => 'Please provide a valid end date.',
            'end_date.after' => 'End date must be after the start date.',
        ];
    }
}
