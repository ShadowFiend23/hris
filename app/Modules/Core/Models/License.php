<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'license_key',
        'type',
        'valid_from',
        'valid_until',
        'status',
        'user_limit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'user_limit' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'license_modules');
    }

    public function isValid(): bool
    {
        return $this->status === 'active' && now()->between($this->valid_from, $this->valid_until);
    }
}
