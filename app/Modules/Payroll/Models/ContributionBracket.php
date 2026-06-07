<?php

namespace App\Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class ContributionBracket extends Model
{
    protected $fillable = [
        'type',
        'effective_date',
        'min_salary',
        'max_salary',
        'employee_rate',
        'employer_rate',
        'employee_amount',
        'employer_amount',
        'min_contribution',
        'max_contribution',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'employee_rate' => 'decimal:4',
            'employer_rate' => 'decimal:4',
            'employee_amount' => 'decimal:2',
            'employer_amount' => 'decimal:2',
            'min_contribution' => 'decimal:2',
            'max_contribution' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
