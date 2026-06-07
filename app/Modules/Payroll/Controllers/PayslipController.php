<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayslipController extends Controller
{
    public function show(PayrollItem $payrollItem): InertiaResponse
    {
        $this->authorize('view', $payrollItem);

        $payrollItem->load([
            'period.setting',
            'employee.company',
            'employee.department:id,name',
            'employee.position:id,position_name',
            'earnings',
            'deductions',
        ]);

        return Inertia::render('Payroll/Payslip', [
            'payrollItem' => $payrollItem,
        ]);
    }

    public function download(PayrollItem $payrollItem): Response|StreamedResponse
    {
        $this->authorize('view', $payrollItem);

        $payrollItem->load([
            'period.setting',
            'employee.company',
            'employee.department:id,name',
            'employee.position:id,position_name',
            'earnings',
            'deductions',
        ]);

        $pdf = Pdf::loadView('payroll.payslip', ['item' => $payrollItem])
            ->setPaper('a4', 'portrait');

        $filename = sprintf(
            'payslip-%s-%s-%s.pdf',
            str_replace(' ', '_', strtolower($payrollItem->employee->last_name)),
            $payrollItem->period->start_date->format('Y'),
            $payrollItem->period->start_date->format('m')
        );

        return $pdf->download($filename);
    }
}
