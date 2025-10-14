<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240', // 10MB
            ],
            'import_type' => [
                'required',
                'string',
                Rule::in(['staff', 'bank_details', 'monthly_payments']),
            ],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV or Excel file (csv, txt, xlsx, xls).',
            'file.max' => 'The file size must not exceed 10MB.',
            'import_type.required' => 'Please select an import type.',
            'import_type.in' => 'Invalid import type selected.',
        ];
    }
}
