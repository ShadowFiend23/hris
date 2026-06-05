<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            // Required fields
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                }),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'date_hired' => ['required', 'date', 'before_or_equal:today'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)->whereNull('deleted_at');
                }),
            ],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where(function ($query) use ($companyId) {
                    return $query->whereIn('department_id', function ($subQuery) use ($companyId) {
                        $subQuery->select('id')
                            ->from('departments')
                            ->where('company_id', $companyId)
                            ->whereNull('deleted_at');
                    })->whereNull('deleted_at');
                }),
            ],

            // Optional personal fields
            'middle_name' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'phone' => ['nullable', 'string', 'max:20'],

            // Optional address fields
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            // Employment fields
            'employment_status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'resigned', 'terminated', 'retired'])],
            'employment_type' => ['nullable', 'string', Rule::in(['full_time', 'part_time', 'contract', 'probationary'])],

            // Compensation
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],

            // Banking
            'bank_account' => ['nullable', 'string', 'max:50'],

            // Government IDs (Philippine standards)
            'tin' => ['nullable', 'string', 'max:15', 'regex:/^[0-9-]+$/'],
            'sss_number' => ['nullable', 'string', 'max:12', 'regex:/^[0-9-]+$/'],
            'philhealth_number' => ['nullable', 'string', 'max:14', 'regex:/^[0-9-]+$/'],
            'pagibig_number' => ['nullable', 'string', 'max:14', 'regex:/^[0-9-]+$/'],

            // Profile photo
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.unique' => 'This employee ID already exists in your company.',
            'email.unique' => 'This email address is already registered to another employee.',
            'department_id.exists' => 'The selected department does not exist or does not belong to your company.',
            'position_id.exists' => 'The selected position does not exist.',
            'date_hired.before_or_equal' => 'The hire date cannot be in the future.',
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'tin.regex' => 'The TIN must contain only numbers and dashes.',
            'sss_number.regex' => 'The SSS number must contain only numbers and dashes.',
            'philhealth_number.regex' => 'The PhilHealth number must contain only numbers and dashes.',
            'pagibig_number.regex' => 'The Pag-IBIG number must contain only numbers and dashes.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee number',
            'first_name' => 'first name',
            'middle_name' => 'middle name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'date_hired' => 'hire date',
            'department_id' => 'department',
            'position_id' => 'position',
            'employment_status' => 'employment status',
            'employment_type' => 'employment type',
            'postal_code' => 'postal code',
            'bank_account' => 'bank account',
            'tin' => 'TIN',
            'sss_number' => 'SSS number',
            'philhealth_number' => 'PhilHealth number',
            'pagibig_number' => 'Pag-IBIG number',
        ];
    }
}
