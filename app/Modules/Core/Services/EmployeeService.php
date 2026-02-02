<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\Module;
use App\Modules\Core\Models\Employee;
use Illuminate\Database\Eloquent\Collection;

class EmployeeService
{
    public function getAllEmployee(): Collection
    {
        return Employee::all();
    }

    public function getEmployeeWithPagination(int $page = 1,int $limit = 10): Collection
    {
        return Employee::skip(($page - 1) * $limit)
                    ->take($limit)
                    ->get();
    }

    public function getEmployeeById(int $id): ?Employee
    {
        return Employee::with(['position', 'department', 'company'])->find($id);
    }
}
