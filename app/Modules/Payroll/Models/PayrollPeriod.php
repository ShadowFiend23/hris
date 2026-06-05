<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'company_id',
        'payroll_setting_id',
        'start_date',
        'end_date',
        'cutoff_start_date',
        'cutoff_end_date',
        'pay_date',
        'status',
        'processed_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'payroll_setting_id' => 'integer',
            'processed_by' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'cutoff_start_date' => 'date',
            'cutoff_end_date' => 'date',
            'pay_date' => 'date',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(PayrollSetting::class, 'payroll_setting_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}
