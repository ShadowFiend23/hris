<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'company_id',
        'type',
        'loan_type_id',
        'principal',
        'balance',
        'monthly_amortization',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'company_id' => 'integer',
            'loan_type_id' => 'integer',
            'principal' => 'decimal:2',
            'balance' => 'decimal:2',
            'monthly_amortization' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
