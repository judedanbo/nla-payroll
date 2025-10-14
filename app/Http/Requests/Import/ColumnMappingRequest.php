<?php

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

class ColumnMappingRequest extends FormRequest
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
            'mapping' => [
                'required',
                'array',
                'min:1',
            ],
            'mapping.*.csv_column' => [
                'required',
                'string',
            ],
            'mapping.*.db_field' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'mapping.required' => 'Column mapping is required.',
            'mapping.array' => 'Invalid column mapping format.',
            'mapping.min' => 'At least one column must be mapped.',
            'mapping.*.csv_column.required' => 'CSV column name is required.',
            'mapping.*.csv_column.string' => 'CSV column name must be a string.',
            'mapping.*.db_field.required' => 'Database field is required.',
            'mapping.*.db_field.string' => 'Database field must be a string.',
        ];
    }
}
