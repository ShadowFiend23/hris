<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeAllowanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'allowance_type_id' => ['required', 'integer', 'exists:allowance_types,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'string', 'in:monthly,per_cutoff'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'allowance_type_id.required' => 'Please select an allowance type.',
            'allowance_type_id.exists' => 'The selected allowance type is invalid.',
            'name.max' => 'Name cannot exceed 100 characters.',
            'amount.required' => 'Please enter the allowance amount.',
            'amount.min' => 'Amount must be zero or greater.',
            'frequency.in' => 'Frequency must be monthly or per_cutoff.',
        ];
    }
}
