<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSetting extends Model
{
    protected $fillable = [
        'company_id',
        'period_type',
        'pay_day_1',
        'pay_day_2',
        'work_days_per_month',
        'cutoff_offset_days',
        'night_differential_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'pay_day_1' => 'integer',
            'pay_day_2' => 'integer',
            'work_days_per_month' => 'integer',
            'cutoff_offset_days' => 'integer',
            'night_differential_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }
}
