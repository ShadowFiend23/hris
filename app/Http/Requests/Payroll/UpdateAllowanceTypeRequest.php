<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllowanceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payroll.settings') ?? false;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $allowanceTypeId = $this->route('allowanceType')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
