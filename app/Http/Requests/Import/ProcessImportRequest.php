<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class ProcessImportRequest extends FormRequest
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
            'column_mapping' => [
                'required',
                'array',
                'min:1',
            ],
            'column_mapping.*' => [
                'required',
                'string',
            ],
            'skip_duplicates' => [
                'sometimes',
                'boolean',
            ],
            'validate_only' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'column_mapping.required' => 'Column mapping is required.',
            'column_mapping.array' => 'Invalid column mapping format.',
            'column_mapping.min' => 'At least one column must be mapped.',
            'column_mapping.*.required' => 'All columns must be mapped to a field.',
            'column_mapping.*.string' => 'Column mapping values must be strings.',
            'skip_duplicates.boolean' => 'Skip duplicates must be true or false.',
            'validate_only.boolean' => 'Validate only must be true or false.',
        ];
    }
}
