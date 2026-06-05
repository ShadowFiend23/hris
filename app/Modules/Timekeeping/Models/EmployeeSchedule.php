<?php

namespace App\Modules\Timekeeping\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSchedule extends Model
{
    protected $table = 'employee_schedules';

    protected $fillable = [
        'employee_id',
        'shift_template_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'shift_template_id' => 'integer',
            'date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function swapRequestsAsRequester(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class, 'requester_schedule_id');
    }

    public function swapRequestsAsTarget(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class, 'target_schedule_id');
    }

    // Scopes
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'off' => 'bg-gray-100 text-gray-800',
            'swapped' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        $start = $this->start_time?->format('h:i A') ?? '';
        $end = $this->end_time?->format('h:i A') ?? '';

        return "{$start} - {$end}";
    }
}
