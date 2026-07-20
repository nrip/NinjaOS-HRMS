<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super_admin', 'central_hr', 'location_hr']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'location_id' => 'required|exists:locations,id',
            'dry_run' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to import.',
            'csv_file.mimes' => 'The file must be a CSV file.',
            'csv_file.max' => 'The file size must not exceed 10MB.',
            'location_id.required' => 'Please select a location.',
            'location_id.exists' => 'The selected location does not exist.',
        ];
    }
}
