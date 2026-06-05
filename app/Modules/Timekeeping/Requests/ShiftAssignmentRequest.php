<?php

namespace App\Modules\Timekeeping\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper admin authorization check
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'shift_template_id.required' => 'Please select a shift template.',
            'shift_template_id.exists' => 'The selected shift template is invalid.',
            'date.required' => 'Please select a date.',
            'date.after_or_equal' => 'Cannot assign shifts for past dates.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee',
            'shift_template_id' => 'shift template',
        ];
    }
}
