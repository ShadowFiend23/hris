<?php

namespace App\Modules\Timekeeping\Models;

use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'remaining_days',
        'carried_over_days',
    ];

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'year' => 'integer',
            'total_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'remaining_days' => 'decimal:2',
            'carried_over_days' => 'decimal:2',
        ];
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Scopes
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    // Methods
    public function deductDays(float $days): bool
    {
        if ($this->remaining_days < $days) {
            return false;
        }

        $this->used_days += $days;
        $this->remaining_days -= $days;
        $this->save();

        return true;
    }

    public function addDays(float $days): void
    {
        $this->used_days -= $days;
        $this->remaining_days += $days;
        $this->save();
    }

    public function canTakeLeave(float $days): bool
    {
        return $this->remaining_days >= $days;
    }

    public function recalculateRemaining(): void
    {
        $this->remaining_days = $this->total_days + $this->carried_over_days - $this->used_days;
        $this->save();
    }
}
