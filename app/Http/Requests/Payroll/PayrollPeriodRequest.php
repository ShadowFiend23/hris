<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.run') ?? false;
    }

    public function rules(): array
    {
        return [
            'payroll_setting_id' => ['required', 'exists:payroll_settings,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['required', 'date', 'after_or_equal:end_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_setting_id.required' => 'Please select a payroll setting.',
            'payroll_setting_id.exists' => 'The selected payroll setting is invalid.',
            'start_date.required' => 'Please enter a start date.',
            'end_date.required' => 'Please enter an end date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'pay_date.required' => 'Please enter a pay date.',
            'pay_date.after_or_equal' => 'Pay date must be on or after the end date.',
        ];
    }
}
