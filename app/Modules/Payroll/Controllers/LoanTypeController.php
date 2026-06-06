<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\LoanTypeRequest;
use App\Modules\Core\Models\Company;
use App\Modules\Payroll\Models\LoanType;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $companyId = $request->user()->company_id;
        $company = Company::findOrFail($companyId);

        $loanTypes = LoanType::where(function ($q) use ($companyId): void {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        })
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 0 ELSE 1 END, name')
            ->get();

        return Inertia::render('HRSettings/LoanTypes', [
            'loanTypes' => $loanTypes,
            'loansEnabled' => $company->loans_enabled,
        ]);
    }

    public function toggleLoans(Request $request): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $company = Company::findOrFail($request->user()->company_id);
        $company->update(['loans_enabled' => ! $company->loans_enabled]);

        $status = $company->loans_enabled ? 'enabled' : 'disabled';

        return redirect()->route('hr-settings.loan-types.index')
            ->with('success', "Loans have been {$status} for your company.");
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $types = LoanType::where(function ($q) use ($companyId): void {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        })
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 0 ELSE 1 END, name')
            ->get()
            ->map(fn (LoanType $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'max_amount' => $t->max_amount !== null ? (float) $t->max_amount : null,
                'is_company' => $t->company_id !== null,
            ]);

        return response()->json($types);
    }

    public function store(LoanTypeRequest $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        LoanType::create(array_merge($request->validated(), [
            'company_id' => $companyId,
            'is_active' => true,
        ]));

        return redirect()->route('hr-settings.loan-types.index')
            ->with('success', "Loan type \"{$request->input('name')}\" created.");
    }

    public function update(LoanTypeRequest $request, LoanType $loanType): RedirectResponse
    {
        abort_if((int) $loanType->company_id !== (int) $request->user()->company_id, 403);

        $loanType->update($request->validated());

        return redirect()->route('hr-settings.loan-types.index')
            ->with('success', "Loan type \"{$loanType->name}\" updated.");
    }

    public function destroy(Request $request, LoanType $loanType): RedirectResponse
    {
        abort_if((int) $loanType->company_id !== (int) $request->user()->company_id, 403);

        $loanType->update(['is_active' => false]);

        return redirect()->route('hr-settings.loan-types.index')
            ->with('success', "Loan type \"{$loanType->name}\" removed.");
    }
}
