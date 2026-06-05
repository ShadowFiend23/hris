<?php

namespace App\Modules\Core\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    public $timestamps = true;

    protected $table = 'modules';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected static function newFactory(): ModuleFactory
    {
        return ModuleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(LicenseModule::class);
    }
}
