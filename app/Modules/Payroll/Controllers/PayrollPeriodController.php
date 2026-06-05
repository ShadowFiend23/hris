<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayrollPeriodRequest;
use App\Jobs\ProcessPayrollJob;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $companyId = request()->user()->employee?->company_id;

        $perPage = max(5, min(100, (int) request()->input('per_page', 5)));

        $periods = PayrollPeriod::with(['processedBy:id,name'])
            ->where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);

        $setting = PayrollSetting::where('company_id', $companyId)->first();

        return Inertia::render('Payroll/Periods', [
            'periods' => $periods,
            'setting' => $setting,
        ]);
    }

    public function store(PayrollPeriodRequest $request): RedirectResponse
    {
        $companyId = $request->user()->employee?->company_id;
        $validated = $request->validated();

        // Auto-calculate cutoff dates if not explicitly provided
        if (empty($validated['cutoff_start_date']) || empty($validated['cutoff_end_date'])) {
            $setting = PayrollSetting::where('company_id', $companyId)->first();
            $offsetDays = $setting?->cutoff_offset_days ?? 15;
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $validated['cutoff_end_date'] = $startDate->copy()->subDays(1)->toDateString();
            $validated['cutoff_start_date'] = $startDate->copy()->subDays($offsetDays)->toDateString();
        }

        PayrollPeriod::create(array_merge($validated, [
            'company_id' => $companyId,
            'status' => 'draft',
        ]));

        return redirect()->back()->with('success', 'Payroll period created.');
    }

    public function show(PayrollPeriod $payrollPeriod): Response
    {
        $this->authorize('view', $payrollPeriod);

        $payrollPeriod->load([
            'items.employee:id,first_name,last_name,employee_id',
            'items.earnings',
            'items.deductions',
        ]);

        return Inertia::render('Payroll/PeriodShow', [
            'period' => $payrollPeriod,
        ]);
    }

    public function run(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('run', $payrollPeriod);

        if (! $payrollPeriod->isDraft()) {
            return redirect()->back()->withErrors(['period' => 'Only draft periods can be processed.']);
        }

        ProcessPayrollJob::dispatch($payrollPeriod->id, request()->user()->id);

        return redirect()->back()->with('success', 'Payroll processing has been queued.');
    }

    public function finalize(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('finalize', $payrollPeriod);

        if ($payrollPeriod->status !== 'processing') {
            return redirect()->back()->withErrors(['period' => 'Only processed periods can be finalized.']);
        }

        $payrollPeriod->update(['status' => 'finalized']);

        return redirect()->back()->with('success', 'Payroll period finalized.');
    }
}
