<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employee::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'location_id' => 'required|exists:locations,id',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'reporting_manager_id' => 'nullable|exists:employees,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('employees')->where('location_id', $this->location_id),
            ],
            'phone' => 'required|regex:/^[0-9]{10}$/',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'aadhaar' => 'nullable|regex:/^[0-9]{12}$/|unique:employees,aadhaar',
            'pan' => 'nullable|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|unique:employees,pan',
            'bank_account' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'ifsc_code' => 'nullable|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'date_of_joining' => 'required|date|after_or_equal:today',
            'probation_end_date' => 'nullable|date|after:date_of_joining',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be 10 digits.',
            'aadhaar.regex' => 'Aadhaar must be 12 digits.',
            'pan.regex' => 'PAN format is invalid (e.g., ABCDE1234F).',
            'ifsc_code.regex' => 'IFSC code format is invalid.',
            'email.unique' => 'Email already exists for this location.',
        ];
    }
}
