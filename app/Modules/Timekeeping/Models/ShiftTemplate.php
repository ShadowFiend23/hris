<?php

namespace App\Modules\Timekeeping\Models;

use App\Modules\Core\Models\Company;
use Database\Factories\ShiftTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    use HasFactory;

    protected static function newFactory(): ShiftTemplateFactory
    {
        return ShiftTemplateFactory::new();
    }

    protected $table = 'shift_templates';

    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'duration_hours',
        'break_duration',
        'break_start_time',
        'break_end_time',
        'work_days',
        'is_active',
        'swap_enabled',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'duration_hours' => 'decimal:2',
            'break_duration' => 'integer',
            'work_days' => 'array',
            'is_active' => 'boolean',
            'swap_enabled' => 'boolean',
        ];
    }

    public function hasSplitShift(): bool
    {
        return $this->break_start_time !== null && $this->break_end_time !== null;
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function scheduleChangeRequests(): HasMany
    {
        return $this->hasMany(ScheduleChangeRequest::class, 'requested_shift_template_id');
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

    // Accessors
    public function getFormattedTimeRangeAttribute(): string
    {
        $start = $this->start_time?->format('h:i A') ?? '';
        $end = $this->end_time?->format('h:i A') ?? '';

        return "{$start} - {$end}";
    }
}
