<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.holidays') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:regular,special'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a holiday name.',
            'date.required' => 'Please enter the holiday date.',
            'type.required' => 'Please select a holiday type.',
            'type.in' => 'Holiday type must be regular or special.',
        ];
    }
}
