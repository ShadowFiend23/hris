<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\LoanRequest;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\LoanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewLoans', Loan::class);

        $user = Auth::user();
        $companyId = $user->employee?->company_id;
        $isAdmin = $user->hasPermission('payroll.loans');
        $perPage = max(5, min(100, (int) request()->input('per_page', 10)));

        if ($isAdmin) {
            $loans = Loan::with('employee:id,first_name,last_name,employee_id')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $summary = [
                'active_count' => Loan::where('company_id', $companyId)->where('status', 'active')->count(),
                'total_balance' => (float) Loan::where('company_id', $companyId)->where('status', 'active')->sum('balance'),
                'total_monthly_deduction' => (float) Loan::where('company_id', $companyId)->where('status', 'active')->sum('monthly_amortization'),
                'employee_count' => Loan::where('company_id', $companyId)->where('status', 'active')->distinct('employee_id')->count('employee_id'),
            ];

            $loanTypes = LoanType::where(function ($q) use ($companyId): void {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
                ->where('is_active', true)
                ->orderByRaw('CASE WHEN company_id IS NULL THEN 0 ELSE 1 END, name')
                ->get(['id', 'name', 'code']);

            return Inertia::render('Payroll/Loans', [
                'loans' => $loans,
                'summary' => $summary,
                'is_admin' => true,
                'loan_types' => $loanTypes,
            ]);
        }

        // Employee: own loans only
        $employeeId = $user->employee?->id;

        $loans = Loan::where('employee_id', $employeeId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $summary = [
            'active_count' => Loan::where('employee_id', $employeeId)->where('status', 'active')->count(),
            'total_balance' => (float) Loan::where('employee_id', $employeeId)->where('status', 'active')->sum('balance'),
            'total_monthly_deduction' => (float) Loan::where('employee_id', $employeeId)->where('status', 'active')->sum('monthly_amortization'),
        ];

        return Inertia::render('Payroll/Loans', [
            'loans' => $loans,
            'summary' => $summary,
            'is_admin' => false,
        ]);
    }

    public function store(LoanRequest $request): RedirectResponse
    {
        $companyId = $request->user()->employee?->company_id;

        $data = $request->validated();

        Loan::create(array_merge($data, [
            'company_id' => $companyId,
            'balance' => $data['principal'],
            'status' => 'active',
        ]));

        return redirect()->back()->with('success', 'Loan added successfully.');
    }

    public function update(LoanRequest $request, Loan $loan): RedirectResponse
    {
        $this->authorize('update', $loan);

        $loan->update($request->validated());

        return redirect()->back()->with('success', 'Loan updated successfully.');
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $this->authorize('delete', $loan);

        $loan->delete();

        return redirect()->back()->with('success', 'Loan deleted.');
    }
}
