<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_status' => [
                'required',
                Rule::in(['onboarding', 'probation', 'confirmed', 'transferred', 'on_leave', 'suspended', 'exit']),
            ],
            'reason' => 'nullable|string|max:500',
            'probation_end_date' => 'required_if:new_status,probation|nullable|date|after:today',
            'confirmation_date' => 'required_if:new_status,confirmed|nullable|date',
            'date_of_exit' => 'required_if:new_status,exit|nullable|date|after_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'new_status.required' => 'New status is required.',
            'new_status.in' => 'Invalid status provided.',
            'probation_end_date.required_if' => 'Probation end date is required when transitioning to probation.',
            'confirmation_date.required_if' => 'Confirmation date is required when confirming employee.',
            'date_of_exit.required_if' => 'Exit date is required when exiting employee.',
        ];
    }
}
