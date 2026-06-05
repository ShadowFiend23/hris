<?php

namespace App\Modules\Timekeeping\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OvertimeRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->employee !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:12',
            'overtime_type' => 'nullable|in:weekday,weekend,holiday',
            'reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Please select a date for the overtime.',
            'hours.required' => 'Please specify the number of overtime hours.',
            'hours.numeric' => 'Hours must be a number.',
            'hours.min' => 'Minimum overtime is 0.5 hours.',
            'hours.max' => 'Maximum overtime is 12 hours per day.',
            'overtime_type.in' => 'Invalid overtime type.',
            'reason.max' => 'The reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'overtime_type' => 'overtime type',
        ];
    }
}
