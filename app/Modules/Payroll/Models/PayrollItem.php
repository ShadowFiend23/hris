<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'basic_pay',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'total_hours',
        'days_worked',
        'days_absent',
        'minutes_late',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payroll_period_id' => 'integer',
            'employee_id' => 'integer',
            'basic_pay' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'total_hours' => 'decimal:2',
            'days_worked' => 'decimal:2',
            'days_absent' => 'decimal:2',
            'minutes_late' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollEarning::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }
}
