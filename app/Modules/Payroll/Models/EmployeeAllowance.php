<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAllowance extends Model
{
    protected $fillable = [
        'employee_id',
        'company_id',
        'allowance_type_id',
        'type',
        'name',
        'amount',
        'is_taxable',
        'frequency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'company_id' => 'integer',
            'allowance_type_id' => 'integer',
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allowanceType(): BelongsTo
    {
        return $this->belongsTo(AllowanceType::class);
    }
}
