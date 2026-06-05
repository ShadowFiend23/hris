<?php

namespace App\Http\Requests\Timekeeping;

use Illuminate\Foundation\Http\FormRequest;

class AdjustAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('manager') || $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date'],
            'clock_out' => ['nullable', 'date', 'after:clock_in'],
            'adjustment_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => 'Clock-in time is required.',
            'clock_in.date' => 'Clock-in must be a valid date and time.',
            'clock_out.date' => 'Clock-out must be a valid date and time.',
            'clock_out.after' => 'Clock-out must be after clock-in.',
            'adjustment_reason.required' => 'A reason for the adjustment is required.',
            'adjustment_reason.min' => 'Reason must be at least 5 characters.',
        ];
    }
}
