<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $companyId = $request->user()->company_id;

        $data = $request->validate([
            'position_name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'reports_to_position_id' => ['nullable', 'integer', 'exists:positions,id'],
        ]);

        if (! empty($data['reports_to_position_id'])) {
            $reportsTo = Position::whereHas('department', fn ($q) => $q->where('company_id', $companyId))
                ->find($data['reports_to_position_id']);

            if (! $reportsTo) {
                return back()->withErrors(['reports_to_position_id' => 'Invalid position.']);
            }
        }

        Position::create($data);

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Position created successfully.');
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $companyId = $request->user()->company_id;
        $this->authorizePosition($position, $companyId);

        $data = $request->validate([
            'position_name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('company_id', $companyId)->whereNull('deleted_at')],
            'reports_to_position_id' => ['nullable', 'integer', 'exists:positions,id', Rule::notIn([$position->id])],
        ]);

        if (! empty($data['reports_to_position_id'])) {
            $reportsTo = Position::whereHas('department', fn ($q) => $q->where('company_id', $companyId))
                ->find($data['reports_to_position_id']);

            if (! $reportsTo) {
                return back()->withErrors(['reports_to_position_id' => 'Invalid position.']);
            }

            if ($position->wouldCreateCycle((int) $data['reports_to_position_id'])) {
                return back()->withErrors(['reports_to_position_id' => 'This would create a circular reporting structure.']);
            }
        }

        $position->update($data);

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Position updated successfully.');
    }

    public function toggle(Request $request, Position $position): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $this->authorizePosition($position, $request->user()->company_id);

        $position->update(['is_active' => ! $position->is_active]);

        $status = $position->is_active ? 'activated' : 'deactivated';

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', "Position {$status}.");
    }

    public function destroy(Request $request, Position $position): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $this->authorizePosition($position, $request->user()->company_id);

        if ($position->employees()->count() > 0) {
            return redirect()->route('app-settings.employee-settings.index')
                ->with('error', 'Cannot delete a position with assigned employees.');
        }

        $position->delete();

        return redirect()->route('app-settings.employee-settings.index')
            ->with('success', 'Position deleted successfully.');
    }

    private function authorizePosition(Position $position, int $companyId): void
    {
        $departmentCompanyId = (int) Department::where('id', $position->department_id)->value('company_id');

        if ($departmentCompanyId !== $companyId) {
            abort(403);
        }
    }
}
