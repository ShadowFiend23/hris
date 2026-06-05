<?php

namespace App\Modules\Timekeeping\Models;

use App\Models\User;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwapRequest extends Model
{
    protected $table = 'shift_swap_requests';

    protected $fillable = [
        'requester_id',
        'target_employee_id',
        'requester_schedule_id',
        'target_schedule_id',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'requester_id' => 'integer',
            'target_employee_id' => 'integer',
            'requester_schedule_id' => 'integer',
            'target_schedule_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function requesterSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'requester_schedule_id');
    }

    public function targetSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'target_schedule_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForRequester(Builder $query, int $employeeId): Builder
    {
        return $query->where('requester_id', $employeeId);
    }

    public function scopeForTarget(Builder $query, int $employeeId): Builder
    {
        return $query->where('target_employee_id', $employeeId);
    }

    // Methods
    public function approve(User $approver): void
    {
        $this->status = 'approved';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->save();
    }

    public function reject(User $approver, string $reason): void
    {
        $this->status = 'rejected';
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
