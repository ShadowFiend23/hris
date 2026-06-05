<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\OvertimeRecord;
use App\Modules\Timekeeping\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function __construct(
        private OvertimeService $overtimeService
    ) {}

    /**
     * Create overtime request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:12',
            'overtime_type' => 'nullable|in:weekday,weekend,holiday',
            'reason' => 'nullable|string|max:500',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        try {
            $record = $this->overtimeService->createOvertimeRequest($employee, $request->all());

            return response()->json([
                'message' => 'Overtime request submitted',
                'data' => $record,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get overtime history
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $filters = $request->only(['status', 'overtime_type', 'start_date', 'end_date', 'per_page']);
        $history = $this->overtimeService->getOvertimeHistory($employee, $filters);

        return response()->json($history);
    }

    /**
     * Get overtime summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $summary = $this->overtimeService->getOvertimeSummary($employee, $startDate, $endDate);

        return response()->json(['data' => $summary]);
    }

    /**
     * Cancel overtime request
     */
    public function cancel(Request $request, OvertimeRecord $overtime): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee || (int) $overtime->employee_id !== (int) $employee->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (! $overtime->isPending()) {
            return response()->json(['error' => 'Can only cancel pending requests'], 400);
        }

        $overtime->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Overtime request cancelled',
            'data' => $overtime->fresh(),
        ]);
    }

    /**
     * Get pending requests (admin)
     */
    public function pending(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $pending = $this->overtimeService->getPendingOvertimeRequests($companyId);

        return response()->json(['data' => $pending]);
    }

    /**
     * Approve overtime request (admin)
     */
    public function approve(Request $request, OvertimeRecord $overtime): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $overtime->employee);
        $this->authorize('manageOvertime', $overtime);

        try {
            $overtime = $this->overtimeService->approveOvertimeRequest($overtime, $request->user());

            return response()->json([
                'message' => 'Overtime request approved',
                'data' => $overtime,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject overtime request (admin)
     */
    public function reject(Request $request, OvertimeRecord $overtime): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $overtime->employee);
        $this->authorize('manageOvertime', $overtime);

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        try {
            $overtime = $this->overtimeService->rejectOvertimeRequest(
                $overtime,
                $request->user(),
                $request->rejection_reason
            );

            return response()->json([
                'message' => 'Overtime request rejected',
                'data' => $overtime,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Mark overtime as paid (admin)
     */
    public function markPaid(Request $request, OvertimeRecord $overtime): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $overtime->employee);
        $this->authorize('manageOvertime', $overtime);

        try {
            $overtime = $this->overtimeService->markAsPaid($overtime);

            return response()->json([
                'message' => 'Overtime marked as paid',
                'data' => $overtime,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get employee overtime (admin)
     */
    public function employeeOvertime(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $filters = $request->only(['status', 'overtime_type', 'start_date', 'end_date', 'per_page']);
        $history = $this->overtimeService->getOvertimeHistory($employee, $filters);

        return response()->json($history);
    }

    /**
     * Get employee overtime summary (admin)
     */
    public function employeeSummary(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $summary = $this->overtimeService->getOvertimeSummary($employee, $startDate, $endDate);

        return response()->json(['data' => $summary]);
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
