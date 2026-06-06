<?php

namespace App\Modules\Timekeeping\Models;

use App\Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimekeepingApprovalSetting extends Model
{
    protected $table = 'timekeeping_approval_settings';

    protected $fillable = [
        'company_id',
        'type',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'steps' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
