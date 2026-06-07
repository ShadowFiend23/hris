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
            'pay_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'pay_date.required' => 'Please enter a pay date.',
            'pay_date.date' => 'Pay date must be a valid date.',
        ];
    }
}
