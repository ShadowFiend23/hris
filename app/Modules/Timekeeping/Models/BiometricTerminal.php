<?php

namespace App\Modules\Timekeeping\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricTerminal extends Model
{
    protected $fillable = [
        'alpeta_terminal_id',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'alpeta_terminal_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    public function isOut(): bool
    {
        return $this->type === 'out';
    }
}
