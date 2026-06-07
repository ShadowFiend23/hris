<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $companyId = $request->user()->company_id;

        Department::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $this->authorizeDepartment($department, $request->user()->company_id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Department updated successfully.');
    }

    public function toggle(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $this->authorizeDepartment($department, $request->user()->company_id);

        $department->update(['is_active' => ! $department->is_active]);

        $status = $department->is_active ? 'activated' : 'deactivated';

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', "Department {$status}.");
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $this->authorizeDepartment($department, $request->user()->company_id);

        if ($department->employees()->count() > 0) {
            return redirect()->route('app-settings.employee-settings.index')
                ->with('error', 'Cannot delete a department with assigned employees.');
        }

        $department->delete();

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Department deleted successfully.');
    }

    private function authorizeDepartment(Department $department, int $companyId): void
    {
        if ((int) $department->company_id !== $companyId) {
            abort(403);
        }
    }
}
