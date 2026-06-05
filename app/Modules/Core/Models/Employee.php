<?php

namespace App\Modules\Core\Models;

use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EmployeeFactory
    {
        return EmployeeFactory::new();
    }

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'position_id',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'date_hired',
        'date_resigned',
        'employment_status',
        'employment_type',
        'salary',
        'salary_type',
        'alpeta_employee_id',
        'bank_account',
        'tin',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'is_active',
        'supervisor_id',
        'shift_template_id',
        'profile_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'company_id' => 'integer',
            'department_id' => 'integer',
            'position_id' => 'integer',
            'date_of_birth' => 'date',
            'date_hired' => 'date',
            'date_resigned' => 'date',
            'salary' => 'decimal:2',
            'salary_type' => 'string',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    // ==========================================
    // Timekeeping Relationships
    // ==========================================

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return Storage::disk('public')->url($this->profile_photo_path);
        }

        return null;
    }
}
