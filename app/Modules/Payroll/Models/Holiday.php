<?php

namespace App\Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'date',
        'type',
        'is_recurring',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'date' => 'date',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function isRegular(): bool
    {
        return $this->type === 'regular';
    }

    public function isSpecial(): bool
    {
        return $this->type === 'special';
    }
}
