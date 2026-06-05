<?php

namespace App\Modules\Timekeeping\Models;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): AttendanceRecordFactory
    {
        return AttendanceRecordFactory::new();
    }

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'company_id',
        'date',
        'clock_in',
        'morning_out',
        'afternoon_in',
        'clock_out',
        'break_duration',
        'total_hours',
        'status',
        'notes',
        'source',
        'alpeta_log_id',
        'approved_by',
        'approved_at',
        'adjusted_by',
        'adjusted_at',
        'adjustment_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'morning_out' => 'datetime',
            'afternoon_in' => 'datetime',
            'clock_out' => 'datetime',
            'break_duration' => 'integer',
            'total_hours' => 'decimal:2',
            'approved_at' => 'datetime',
            'employee_id' => 'integer',
            'company_id' => 'integer',
            'approved_by' => 'integer',
            'adjusted_by' => 'integer',
            'adjusted_at' => 'datetime',
        ];
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

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

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present' => 'bg-green-100 text-green-800',
            'late' => 'bg-yellow-100 text-yellow-800',
            'absent' => 'bg-red-100 text-red-800',
            'half_day' => 'bg-orange-100 text-orange-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getIsLateAttribute(): bool
    {
        return $this->status === 'late';
    }

    public function getIsClockedInAttribute(): bool
    {
        return $this->clock_in !== null && $this->clock_out === null;
    }

    public function getIsClockedOutAttribute(): bool
    {
        return $this->clock_in !== null && $this->clock_out !== null;
    }
}
