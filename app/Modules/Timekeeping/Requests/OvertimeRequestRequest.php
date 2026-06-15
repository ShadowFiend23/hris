<?php

namespace App\Modules\Timekeeping\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class OvertimeRequestRequest extends FormRequest
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
     *
     * Overtime is captured as a time-in (start date + time) and a time-out
     * (end date + time); the worked hours are derived from this window.
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_date' => 'required|date',
            'end_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Additional cross-field validation: the time-out must be after the time-in
     * and the window cannot exceed 24 hours.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = Carbon::parse($this->input('start_date').' '.$this->input('start_time'));
            $end = Carbon::parse($this->input('end_date').' '.$this->input('end_time'));

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('end_time', 'The time-out must be after the time-in.');

                return;
            }

            if ($start->diffInHours($end) > 24) {
                $validator->errors()->add('end_time', 'Overtime cannot exceed 24 hours.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select the date you started overtime.',
            'start_time.required' => 'Please specify the time you started overtime.',
            'start_time.date_format' => 'The time-in must be a valid time.',
            'end_date.required' => 'Please select the date you ended overtime.',
            'end_time.required' => 'Please specify the time you ended overtime.',
            'end_time.date_format' => 'The time-out must be a valid time.',
            'reason.max' => 'The reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'start_date' => 'time-in date',
            'start_time' => 'time-in',
            'end_date' => 'time-out date',
            'end_time' => 'time-out',
        ];
    }
}
