<?php

namespace App\Modules\Timekeeping\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftSwapRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->employee !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'my_schedule_id' => 'required|exists:employee_schedules,id',
            'target_schedule_id' => 'required|exists:employee_schedules,id|different:my_schedule_id',
            'reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'my_schedule_id.required' => 'Please select your schedule to swap.',
            'my_schedule_id.exists' => 'The selected schedule is invalid.',
            'target_schedule_id.required' => 'Please select a target schedule to swap with.',
            'target_schedule_id.exists' => 'The target schedule is invalid.',
            'target_schedule_id.different' => 'Cannot swap a schedule with itself.',
            'reason.max' => 'The reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'my_schedule_id' => 'your schedule',
            'target_schedule_id' => 'target schedule',
        ];
    }
}
