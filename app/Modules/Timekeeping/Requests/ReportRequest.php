<?php

namespace App\Modules\Timekeeping\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
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
        $rules = [
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:employees,id',
        ];

        // Determine report type and add specific rules
        if ($this->routeIs('*.attendance') || $this->routeIs('*.overtime')) {
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after_or_equal:start_date';
        }

        if ($this->routeIs('*.leave')) {
            $rules['year'] = 'required|integer|min:2020|max:2099';
            $rules['status'] = 'nullable|in:pending,approved,rejected,cancelled';
            $rules['leave_type_id'] = 'nullable|exists:leave_types,id';
        }

        if ($this->routeIs('*.overtime')) {
            $rules['status'] = 'nullable|in:pending,approved,rejected,paid';
            $rules['overtime_type'] = 'nullable|in:weekday,weekend,holiday';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select a start date.',
            'end_date.required' => 'Please select an end date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
            'year.required' => 'Please specify a year.',
            'year.integer' => 'Year must be a valid number.',
            'year.min' => 'Year must be 2020 or later.',
            'year.max' => 'Year cannot exceed 2099.',
            'department_id.exists' => 'The selected department is invalid.',
            'employee_id.exists' => 'The selected employee is invalid.',
            'status.in' => 'Invalid status filter.',
            'leave_type_id.exists' => 'The selected leave type is invalid.',
            'overtime_type.in' => 'Invalid overtime type filter.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'department_id' => 'department',
            'employee_id' => 'employee',
            'leave_type_id' => 'leave type',
            'overtime_type' => 'overtime type',
        ];
    }
}
