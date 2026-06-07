<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreAllowanceTypeRequest;
use App\Http\Requests\Payroll\UpdateAllowanceTypeRequest;
use App\Modules\Payroll\Models\AllowanceType;
use App\Modules\Payroll\Models\EmployeeAllowance;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllowanceTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $companyId = $request->user()->company_id;

        $allowanceTypes = AllowanceType::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return Inertia::render('HRSettings/AllowanceTypes', [
            'allowanceTypes' => $allowanceTypes,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $types = AllowanceType::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (AllowanceType $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'default_amount' => $t->default_amount !== null ? (float) $t->default_amount : null,
                'is_taxable' => (bool) $t->is_taxable,
            ]);

        return response()->json($types);
    }

    public function store(StoreAllowanceTypeRequest $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        AllowanceType::create(array_merge($request->validated(), [
            'company_id' => $companyId,
            'is_active' => true,
        ]));

        return redirect()->route('app-settings.allowance-types.index')
            ->with('success', "Allowance type \"{$request->input('name')}\" created.");
    }

    public function update(UpdateAllowanceTypeRequest $request, AllowanceType $allowanceType): RedirectResponse
    {
        abort_if((int) $allowanceType->company_id !== (int) $request->user()->company_id, 403);

        $allowanceType->update($request->validated());

        return redirect()->route('app-settings.allowance-types.index')
            ->with('success', "Allowance type \"{$allowanceType->name}\" updated.");
    }

    public function destroy(Request $request, AllowanceType $allowanceType): RedirectResponse
    {
        abort_if((int) $allowanceType->company_id !== (int) $request->user()->company_id, 403);

        $name = $allowanceType->name;

        $inUse = EmployeeAllowance::where('allowance_type_id', $allowanceType->id)->exists();
        if ($inUse) {
            return redirect()->route('app-settings.allowance-types.index')
                ->with('error', "Cannot delete \"{$name}\" — it is assigned to one or more employees.");
        }

        $allowanceType->delete();

        return redirect()->route('app-settings.allowance-types.index')
            ->with('success', "Allowance type \"{$name}\" deleted.");
    }
}
