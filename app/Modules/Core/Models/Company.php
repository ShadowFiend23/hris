<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Services\LicenseService;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'companies';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'registration_number',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'website',
        'industry',
        'employee_count',
        'is_active',
        'loans_enabled',
        'leave_enabled',
        'ot_enabled',
        'swap_enabled',
        'logo_login',
        'logo_nav',
        'favicon',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'loans_enabled' => 'boolean',
            'leave_enabled' => 'boolean',
            'ot_enabled' => 'boolean',
            'swap_enabled' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Get the active license for this company
     */
    public function getActiveLicense(): ?License
    {
        $licenseService = app(LicenseService::class);

        return $licenseService->getCompanyActiveLicense($this->id);
    }

    /**
     * Check if the company has access to a specific module
     */
    public function hasModuleAccess(string $moduleCode): bool
    {
        $licenseService = app(LicenseService::class);

        return $licenseService->hasModuleAccess($this->id, $moduleCode);
    }
}
