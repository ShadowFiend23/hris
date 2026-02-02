<?php

namespace App\Modules\Core\Contracts;

interface ModuleContract
{
    /**
     * Get module name
     */
    public function getName(): string;

    /**
     * Get module description
     */
    public function getDescription(): string;

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool;
}
