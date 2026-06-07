<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Role;
use App\Modules\Timekeeping\Models\LeaveType;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use App\Modules\Timekeeping\Models\TimekeepingApprovalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimekeepingSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $companyId = $request->user()->company_id;
        $company = Company::findOrFail($companyId);

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $leaveApproval = TimekeepingApprovalSetting::where('company_id', $companyId)
            ->where('type', 'leave')
            ->first();

        $otApproval = TimekeepingApprovalSetting::where('company_id', $companyId)
            ->where('type', 'ot')
            ->first();

        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);

        $shiftTemplates = ShiftTemplate::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return Inertia::render('HRSettings/LeaveTypes', [
            'leaveTypes' => $leaveTypes,
            'leaveEnabled' => $company->leave_enabled,
            'otEnabled' => $company->ot_enabled,
            'leaveApprovalSteps' => $leaveApproval?->steps ?? [],
            'otApprovalSteps' => $otApproval?->steps ?? [],
            'roles' => $roles,
            'shiftTemplates' => $shiftTemplates,
        ]);
    }

    public function toggleLeave(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $company = Company::findOrFail($request->user()->company_id);
        $company->update(['leave_enabled' => ! $company->leave_enabled]);

        $status = $company->leave_enabled ? 'enabled' : 'disabled';

        return redirect()->route('app-settings.timekeeping.index')
            ->with('success', "Leave has been {$status}.");
    }

    public function toggleOt(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $company = Company::findOrFail($request->user()->company_id);
        $company->update(['ot_enabled' => ! $company->ot_enabled]);

        $status = $company->ot_enabled ? 'enabled' : 'disabled';

        return redirect()->route('app-settings.timekeeping.index')
            ->with('success', "Overtime has been {$status}.");
    }

    public function saveApprovalChain(Request $request): RedirectResponse
    {
        if (! $request->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'type' => ['required', 'in:leave,ot'],
            'steps' => ['required', 'array', 'min:1', 'max:3'],
            'steps.*.order' => ['required', 'integer', 'min:1', 'max:3'],
            'steps.*.role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $companyId = $request->user()->company_id;

        TimekeepingApprovalSetting::updateOrCreate(
            ['company_id' => $companyId, 'type' => $request->type],
            ['steps' => $request->steps]
        );

        $label = $request->type === 'leave' ? 'Leave' : 'Overtime';

        return redirect()->route('app-settings.timekeeping.index')
            ->with('success', "{$label} approval chain saved.");
    }
}
