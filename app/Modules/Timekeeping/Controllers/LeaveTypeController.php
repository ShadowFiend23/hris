<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveType\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveType\UpdateLeaveTypeRequest;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\LeaveBalance;
use App\Modules\Timekeeping\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $companyId = $request->user()->company_id;

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return Inertia::render('HRSettings/LeaveTypes', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $leaveType = LeaveType::create(array_merge(
            $request->validated(),
            ['company_id' => $companyId, 'is_active' => true]
        ));

        // Auto-initialize leave balance for all active employees in the company
        $currentYear = now()->year;

        Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->each(function (Employee $employee) use ($leaveType, $currentYear): void {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $currentYear,
                    ],
                    [
                        'total_days' => $leaveType->days_per_year,
                        'used_days' => 0,
                        'remaining_days' => $leaveType->days_per_year,
                        'carried_over_days' => 0,
                    ]
                );
            });

        return redirect()->route('hr-settings.leave-types.index')
            ->with('success', "Leave type \"{$leaveType->name}\" created and balances provisioned.");
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $leaveType->update($request->validated());

        return redirect()->route('hr-settings.leave-types.index')
            ->with('success', "Leave type \"{$leaveType->name}\" updated.");
    }

    public function destroy(Request $request, LeaveType $leaveType): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        if ($leaveType->company_id !== $request->user()->company_id) {
            abort(403);
        }

        // Soft-deactivate: preserve existing balances
        $leaveType->update(['is_active' => false]);

        return redirect()->route('hr-settings.leave-types.index')
            ->with('success', "Leave type \"{$leaveType->name}\" deactivated.");
    }
}
