<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class PayrollSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_type' => ['required', 'in:weekly,semi_monthly,monthly'],
            'pay_day_1' => ['required', 'integer', 'min:1', 'max:31'],
            'pay_day_2' => ['nullable', 'integer', 'min:1', 'max:31'],
            'work_days_per_month' => ['required', 'integer', 'min:20', 'max:31'],
            'cutoff_offset_days' => ['required', 'integer', 'min:0', 'max:31'],
            'night_differential_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'period_type.required' => 'Please select a payroll period type.',
            'period_type.in' => 'Period type must be weekly, semi-monthly, or monthly.',
            'pay_day_1.required' => 'Please enter the first pay day.',
            'work_days_per_month.required' => 'Please enter work days per month.',
        ];
    }
}
