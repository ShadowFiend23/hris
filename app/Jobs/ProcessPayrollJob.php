<?php

namespace App\Jobs;

use App\Modules\Payroll\Models\PayrollPeriod;
use App\Modules\Payroll\Services\PayrollCalculationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPayrollJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly int $payrollPeriodId,
        public readonly int $processedBy,
    ) {}

    public function handle(PayrollCalculationService $calculator): void
    {
        $period = PayrollPeriod::with('setting')->findOrFail($this->payrollPeriodId);

        $calculator->runPayrollForPeriod($period);

        $period->update([
            'status' => 'finalized',
            'processed_by' => $this->processedBy,
            'processed_at' => now(),
        ]);
    }
}
