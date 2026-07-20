<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->employee !== null;
    }

    public function rules(): array
    {
        return [
            'leave_type'       => ['required', 'string', 'in:EL,CL,SL,ML,PL,BL,CO,UL'],
            'from_date'        => ['required', 'date', 'after_or_equal:today'],
            'to_date'          => ['required', 'date', 'after_or_equal:from_date'],
            'reason'           => ['required', 'string', 'min:10', 'max:500'],
            'is_half_day'      => ['boolean'],
            'half_day_session' => ['nullable', 'required_if:is_half_day,true', 'in:first_half,second_half'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('is_half_day')) {
                $from = $this->input('from_date');
                $to   = $this->input('to_date');
                if ($from !== $to) {
                    $validator->errors()->add('is_half_day', 'Half-day leave must be for a single day (from_date must equal to_date).');
                }
            }
        });
    }
}
