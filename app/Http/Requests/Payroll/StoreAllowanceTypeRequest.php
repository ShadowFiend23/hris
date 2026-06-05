<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllowanceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.settings') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('allowance_types')->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Code must be lowercase letters, numbers, and underscores only (e.g., gas_allowance).',
            'code.unique' => 'This code is already used by another allowance type.',
        ];
    }
}
