<?php

namespace App\Http\Requests\LeaveType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10'],
            'days_per_year' => ['required', 'integer', 'min:1', 'max:365'],
            'is_paid' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
