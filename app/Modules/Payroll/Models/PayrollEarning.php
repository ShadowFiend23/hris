<?php

namespace App\Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEarning extends Model
{
    protected $fillable = [
        'payroll_item_id',
        'type',
        'hours',
        'amount',
        'description',
        'is_taxable',
    ];

    protected function casts(): array
    {
        return [
            'payroll_item_id' => 'integer',
            'hours' => 'decimal:2',
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
