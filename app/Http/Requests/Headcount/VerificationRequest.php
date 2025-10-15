<?php

namespace App\Http\Requests\Headcount;

use App\Models\HeadcountSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Must be authenticated and have Field Auditor or Audit Leader role
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        return $user->hasAnyRole(['Audit Leader', 'Field Auditor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'headcount_session_id' => [
                'required',
                'exists:headcount_sessions,id',
                function ($attribute, $value, $fail) {
                    $session = HeadcountSession::find($value);
                    if ($session && ! $session->isActive()) {
                        $fail('The selected headcount session is not active.');
                    }
                },
            ],
            'staff_id' => [
                'required',
                'exists:staff,id',
            ],
            'station_id' => [
                'required',
                'exists:stations,id',
            ],
            'verification_status' => [
                'required',
                Rule::in(['present', 'absent', 'on_leave', 'ghost']),
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'photo' => [
                // 'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:5120', // 5MB
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'headcount_session_id.required' => 'Headcount session is required.',
            'headcount_session_id.exists' => 'The selected headcount session does not exist.',
            'staff_id.required' => 'Staff member is required.',
            'staff_id.exists' => 'The selected staff member does not exist.',
            'station_id.required' => 'Station is required.',
            'station_id.exists' => 'The selected station does not exist.',
            'verification_status.required' => 'Verification status is required.',
            'verification_status.in' => 'Invalid verification status. Must be present, absent, on_leave, or ghost.',
            'latitude.numeric' => 'Latitude must be a valid number.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.numeric' => 'Longitude must be a valid number.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'photo.required' => 'Verification photo is required.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'Photo must be JPEG, JPG, or PNG format.',
            'photo.max' => 'Photo size must not exceed 5MB.',
            'remarks.max' => 'Remarks must not exceed 1000 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure both latitude and longitude are provided together
            if (($this->latitude && ! $this->longitude) || (! $this->latitude && $this->longitude)) {
                $validator->errors()->add('location', 'Both latitude and longitude must be provided together.');
            }
        });
    }
}
