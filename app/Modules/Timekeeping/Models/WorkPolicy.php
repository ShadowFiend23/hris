<?php

namespace App\Modules\Timekeeping\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPolicy extends Model
{
    protected $table = 'work_policies';

    protected $fillable = [
        'company_id',
        'standard_hours_per_day',
        'standard_hours_per_week',
        'late_threshold_minutes',
        'grace_period_minutes',
        'weekday_overtime_rate',
        'weekend_overtime_rate',
        'holiday_overtime_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'standard_hours_per_day' => 'integer',
            'standard_hours_per_week' => 'integer',
            'late_threshold_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'weekday_overtime_rate' => 'decimal:2',
            'weekend_overtime_rate' => 'decimal:2',
            'holiday_overtime_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    // Methods
    public function getOvertimeRate(string $type): float
    {
        return match ($type) {
            'weekday' => $this->weekday_overtime_rate,
            'weekend' => $this->weekend_overtime_rate,
            'holiday' => $this->holiday_overtime_rate,
            default => $this->weekday_overtime_rate,
        };
    }

    public function isLate(int $minutesLate): bool
    {
        return $minutesLate > ($this->late_threshold_minutes + $this->grace_period_minutes);
    }
}
