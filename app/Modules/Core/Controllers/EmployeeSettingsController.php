<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $companyId = $request->user()->company_id;

        // SQL Server returns withCount aggregates as strings; cast so the frontend's
        // numeric checks (e.g. employees_count === 0) work.
        $departments = Department::where('company_id', $companyId)
            ->withCount('employees')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'is_active']);
        $departments->each(fn ($d) => $d->employees_count = (int) $d->employees_count);

        $positions = Position::whereHas('department', fn ($q) => $q->where('company_id', $companyId))
            ->with(['department:id,name', 'reportsTo:id,position_name'])
            ->withCount('employees')
            ->orderBy('position_name')
            ->get(['id', 'department_id', 'position_name', 'reports_to_position_id', 'is_active']);
        $positions->each(fn ($p) => $p->employees_count = (int) $p->employees_count);

        return Inertia::render('HRSettings/EmployeeSettings', [
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }
}
