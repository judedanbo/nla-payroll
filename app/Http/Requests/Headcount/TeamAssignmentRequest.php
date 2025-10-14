<?php

namespace App\Http\Requests\Headcount;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class TeamAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Audit Leaders can assign teams
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
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if ($user && ! $user->hasAnyRole(['Audit Leader', 'Field Auditor'])) {
                        $fail('The selected user must have Audit Leader or Field Auditor role.');
                    }
                },
            ],
            'station_id' => [
                'required',
                'exists:stations,id',
            ],
            'headcount_session_id' => [
                'nullable',
                'exists:headcount_sessions,id',
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
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'station_id.required' => 'Station is required.',
            'station_id.exists' => 'The selected station does not exist.',
            'headcount_session_id.exists' => 'The selected headcount session does not exist.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Please provide a valid start date.',
            'start_date.after_or_equal' => 'Start date must be today or a future date.',
            'end_date.date' => 'Please provide a valid end date.',
            'end_date.after' => 'End date must be after the start date.',
        ];
    }
}
