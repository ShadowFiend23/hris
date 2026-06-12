<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayrollPeriodRequest;
use App\Jobs\ProcessPayrollJob;
use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Payroll\Services\PayrollCalculationService;
use App\Modules\Payroll\Services\PayrollPeriodService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    public function __construct(
        private readonly PayrollPeriodService $periodService,
        private readonly PayrollCalculationService $calculationService,
    ) {}

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

        // Compute the next period dates so the frontend can display them locked
        $nextPeriod = null;

        if ($setting) {
            $lastPeriod = PayrollPeriod::where('company_id', $companyId)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('end_date', 'desc')
                ->first();

            $referenceDate = $lastPeriod
                ? Carbon::parse($lastPeriod->end_date)->addDay()
                : Carbon::now();

            $generated = $this->periodService->generateNextPeriod($setting, $referenceDate);

            $nextPeriod = [
                'start_date' => $generated['start_date']->toDateString(),
                'end_date' => $generated['end_date']->toDateString(),
                'pay_date' => $generated['pay_date']->toDateString(),
            ];
        }

        return Inertia::render('Payroll/Periods', [
            'periods' => $periods,
            'setting' => $setting,
            'nextPeriod' => $nextPeriod,
        ]);
    }

    public function store(PayrollPeriodRequest $request): RedirectResponse
    {
        $companyId = $request->user()->employee?->company_id;
        $setting = PayrollSetting::where('company_id', $companyId)->firstOrFail();

        $lastPeriod = PayrollPeriod::where('company_id', $companyId)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('end_date', 'desc')
            ->first();

        $referenceDate = $lastPeriod
            ? Carbon::parse($lastPeriod->end_date)->addDay()
            : Carbon::now();

        $generated = $this->periodService->generateNextPeriod($setting, $referenceDate);

        $startDate = $generated['start_date'];
        $endDate = $generated['end_date'];

        // Safety check: reject if the generated period already exists
        $overlap = PayrollPeriod::where('company_id', $companyId)
            ->whereNotIn('status', ['cancelled'])
            ->where('start_date', '<=', $endDate->toDateString())
            ->where('end_date', '>=', $startDate->toDateString())
            ->exists();

        if ($overlap) {
            return redirect()->back()->withErrors([
                'period' => 'A payroll period already exists for these dates.',
            ]);
        }

        $offsetDays = $setting->cutoff_offset_days ?? 15;

        PayrollPeriod::create([
            'company_id' => $companyId,
            'payroll_setting_id' => $setting->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'pay_date' => $request->validated()['pay_date'],
            'cutoff_start_date' => $startDate->copy()->subDays($offsetDays)->toDateString(),
            'cutoff_end_date' => $startDate->copy()->subDays(1)->toDateString(),
            'status' => 'draft',
        ]);

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

        return redirect()->back()->with('success', 'Payroll run has been queued.');
    }

    public function finalize(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('finalize', $payrollPeriod);

        if ($payrollPeriod->status !== 'review') {
            return redirect()->back()->withErrors(['period' => 'Only periods under review can be finalized.']);
        }

        $this->calculationService->finalizePeriod($payrollPeriod);

        return redirect()->back()->with('success', 'Payroll period finalized.');
    }

    public function cancel(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('cancel', $payrollPeriod);

        // Discard any payslips generated during review so a fresh run starts clean.
        $payrollPeriod->items()->each(function ($item): void {
            $item->earnings()->delete();
            $item->deductions()->delete();
        });
        $payrollPeriod->items()->delete();

        $payrollPeriod->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Payroll period cancelled.');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('deletePeriod', $payrollPeriod);

        $payrollPeriod->items()->each(fn ($item) => $item->earnings()->delete() || $item->deductions()->delete());
        $payrollPeriod->items()->delete();
        $payrollPeriod->delete();

        return redirect()->route('payroll.periods.index')->with('success', 'Payroll period deleted.');
    }
}
