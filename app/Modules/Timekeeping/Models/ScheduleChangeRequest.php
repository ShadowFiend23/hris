<?php

namespace App\Modules\Timekeeping\Models;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleChangeRequest extends Model
{
    protected $table = 'schedule_change_requests';

    protected $fillable = [
        'company_id',
        'employee_id',
        'current_schedule_id',
        'requested_shift_template_id',
        'date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'employee_id' => 'integer',
            'current_schedule_id' => 'integer',
            'requested_shift_template_id' => 'integer',
            'date' => 'date',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeSchedule::class, 'current_schedule_id');
    }

    public function requestedShiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'requested_shift_template_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}
