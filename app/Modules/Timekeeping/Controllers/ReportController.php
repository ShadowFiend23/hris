<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Generate attendance report
     */
    public function attendance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $report = $this->reportService->generateAttendanceReport(
            $companyId,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->only(['department_id', 'employee_id'])
        );

        return response()->json($report);
    }

    /**
     * Generate leave report
     */
    public function leave(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected,cancelled',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $report = $this->reportService->generateLeaveReport(
            $companyId,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->only(['status', 'leave_type_id', 'department_id', 'employee_id'])
        );

        return response()->json($report);
    }

    /**
     * Generate overtime report
     */
    public function overtime(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected,paid',
            'overtime_type' => 'nullable|in:weekday,weekend,holiday',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $report = $this->reportService->generateOvertimeReport(
            $companyId,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->only(['status', 'overtime_type', 'department_id', 'employee_id'])
        );

        return response()->json($report);
    }

    /**
     * Generate employee summary report
     */
    public function employeeSummary(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->reportService->generateSummaryReport(
            $employee,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json($report);
    }

    /**
     * Generate my summary report (for current user)
     */
    public function mySummary(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $report = $this->reportService->generateSummaryReport(
            $employee,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        return response()->json($report);
    }

    /**
     * Authorize company access
     */
    private function authorizeCompanyAccess(Request $request, Employee $employee): void
    {
        $userCompanyId = $request->user()->employee?->company_id;

        if ((int) $userCompanyId !== (int) $employee->company_id) {
            abort(403, 'Unauthorized access to this employee');
        }
    }
}
