<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\PayrollItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EmployeePayslipController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        abort_if(
            ! $request->user()->hasPermission('payroll.view_own'),
            403,
            'You do not have permission to view payslips.'
        );

        $employee = $request->user()->employee;

        abort_if(! $employee, 403, 'No employee record found for this user.');

        $items = PayrollItem::with(['period'])
            ->where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q->where('status', 'finalized'))
            ->get()
            ->sortByDesc(fn (PayrollItem $item) => $item->period->pay_date)
            ->values();

        $periods = $items->map(fn (PayrollItem $item) => [
            'payroll_item_id' => $item->id,
            'period_id' => $item->period->id,
            'start_date' => $item->period->start_date->toDateString(),
            'end_date' => $item->period->end_date->toDateString(),
            'pay_date' => $item->period->pay_date->toDateString(),
            'label' => $item->period->start_date->format('M j').'–'.$item->period->end_date->format('M j, Y'),
        ]);

        $latestItem = $items->first();
        $latestPayslip = null;

        if ($latestItem) {
            $latestPayslip = $this->loadPayslipData($latestItem);
        }

        return Inertia::render('Payroll/MyPayslips', [
            'periods' => $periods,
            'latestPayslip' => $latestPayslip,
        ]);
    }

    public function fetch(Request $request, PayrollItem $payrollItem): JsonResponse
    {
        $employee = $request->user()->employee;

        abort_if(! $employee, 403, 'No employee record found for this user.');
        abort_if((int) $payrollItem->employee_id !== (int) $employee->id, 403, 'Forbidden.');

        return response()->json($this->loadPayslipData($payrollItem));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadPayslipData(PayrollItem $item): array
    {
        $item->load([
            'period.setting',
            'employee.company:id,name',
            'employee.department:id,name',
            'employee.position:id,position_name',
            'earnings',
            'deductions',
        ]);

        return [
            'id' => $item->id,
            'basic_pay' => (float) $item->basic_pay,
            'gross_pay' => (float) $item->gross_pay,
            'total_deductions' => (float) $item->total_deductions,
            'net_pay' => (float) $item->net_pay,
            'days_worked' => $item->days_worked,
            'days_absent' => $item->days_absent,
            'total_hours' => (float) $item->total_hours,
            'minutes_late' => $item->minutes_late,
            'period' => [
                'id' => $item->period->id,
                'start_date' => $item->period->start_date->toDateString(),
                'end_date' => $item->period->end_date->toDateString(),
                'cutoff_start_date' => $item->period->cutoff_start_date?->toDateString(),
                'cutoff_end_date' => $item->period->cutoff_end_date?->toDateString(),
                'pay_date' => $item->period->pay_date->toDateString(),
            ],
            'employee' => [
                'first_name' => $item->employee->first_name,
                'last_name' => $item->employee->last_name,
                'middle_name' => $item->employee->middle_name,
                'employee_id' => $item->employee->employee_id,
                'sss_number' => $item->employee->sss_number,
                'philhealth_number' => $item->employee->philhealth_number,
                'pagibig_number' => $item->employee->pagibig_number,
                'tin' => $item->employee->tin,
                'department' => $item->employee->department ? ['name' => $item->employee->department->name] : null,
                'position' => $item->employee->position ? ['name' => $item->employee->position->position_name] : null,
                'company' => $item->employee->company ? ['name' => $item->employee->company->name] : null,
            ],
            'earnings' => $item->earnings->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'amount' => (float) $e->amount,
                'hours' => $e->hours ? (float) $e->hours : null,
                'description' => $e->description,
                'is_taxable' => (bool) $e->is_taxable,
            ])->values(),
            'deductions' => $item->deductions->map(fn ($d) => [
                'id' => $d->id,
                'type' => $d->type,
                'amount' => (float) $d->amount,
                'description' => $d->description,
            ])->values(),
        ];
    }
}
