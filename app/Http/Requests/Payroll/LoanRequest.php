<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class LoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.loans') ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'string', 'max:50'],
            'loan_type_id' => ['nullable', 'exists:loan_types,id'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'monthly_amortization' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'type.required' => 'Please select a loan type.',
            'type.in' => 'Loan type must be SSS, Pag-IBIG, or company loan.',
            'principal.required' => 'Please enter the loan principal amount.',
            'monthly_amortization.required' => 'Please enter the monthly amortization.',
            'start_date.required' => 'Please enter the loan start date.',
        ];
    }
}
