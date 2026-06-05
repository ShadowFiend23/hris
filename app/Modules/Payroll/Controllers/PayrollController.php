<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\Loan;
use App\Modules\Payroll\Models\PayrollPeriod;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(): Response
    {
        $companyId = Auth::user()->employee?->company_id;

        $totalPeriods = PayrollPeriod::where('company_id', $companyId)->count();

        $draftPeriods = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'draft')
            ->count();

        $lastFinalized = PayrollPeriod::where('company_id', $companyId)
            ->where('status', 'finalized')
            ->orderBy('pay_date', 'desc')
            ->first(['id', 'pay_date']);

        $lastNetPay = $lastFinalized
            ? (float) $lastFinalized->items()->sum('net_pay')
            : 0.0;

        $lastEmployeeCount = $lastFinalized
            ? $lastFinalized->items()->count()
            : 0;

        $activeLoans = Loan::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        $recentPeriods = PayrollPeriod::where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get(['id', 'start_date', 'end_date', 'pay_date', 'status'])
            ->map(fn (PayrollPeriod $p) => [
                'id' => $p->id,
                'start_date' => $p->start_date->toDateString(),
                'end_date' => $p->end_date->toDateString(),
                'pay_date' => $p->pay_date->toDateString(),
                'status' => $p->status,
                'employee_count' => $p->items()->count(),
                'total_net' => (float) $p->items()->sum('net_pay'),
            ]);

        return Inertia::render('Payroll/Payroll', [
            'stats' => [
                'total_periods' => $totalPeriods,
                'draft_periods' => $draftPeriods,
                'last_net_pay' => $lastNetPay,
                'last_employee_count' => $lastEmployeeCount,
                'last_pay_date' => $lastFinalized?->pay_date?->toDateString(),
                'active_loans' => $activeLoans,
            ],
            'recent_periods' => $recentPeriods,
        ]);
    }
}
