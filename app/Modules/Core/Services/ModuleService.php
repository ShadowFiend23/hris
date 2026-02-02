<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class ModuleService
{
    public function getAllModules(): Collection
    {
        return Module::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getModuleByCode(string $code): ?Module
    {
        return Module::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    public function isModuleActive(string $code): bool
    {
        return $this->getModuleByCode($code) !== null;
    }
}
