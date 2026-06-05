<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\LeaveRequest;
use App\Modules\Timekeeping\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService
    ) {}

    /**
     * Get leave types
     */
    public function types(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $types = $this->leaveService->getLeaveTypes($companyId);

        return response()->json(['data' => $types]);
    }

    /**
     * Get leave balance for current user
     */
    public function balance(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $year = $request->input('year', now()->year);
        $balance = $this->leaveService->getLeaveBalance($employee, $year);

        return response()->json(['data' => $balance]);
    }

    /**
     * Get leave history
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $filters = $request->only(['status', 'leave_type_id', 'year', 'per_page']);
        $history = $this->leaveService->getEmployeeLeaveHistory($employee, $filters);

        return response()->json($history);
    }

    /**
     * Create leave request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        try {
            $leaveRequest = $this->leaveService->createLeaveRequest($employee, $request->all());

            return response()->json([
                'message' => 'Leave request submitted successfully',
                'data' => $leaveRequest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel leave request
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee || (int) $leaveRequest->employee_id !== (int) $employee->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($leaveRequest->isRejected()) {
            return response()->json(['error' => 'Cannot cancel a rejected request'], 400);
        }

        try {
            $leaveRequest = $this->leaveService->cancelLeaveRequest($leaveRequest);

            return response()->json([
                'message' => 'Leave request cancelled',
                'data' => $leaveRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
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

        $pending = $this->leaveService->getPendingRequests($companyId);

        return response()->json(['data' => $pending]);
    }

    /**
     * Approve leave request (admin)
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $leaveRequest->employee);
        $this->authorize('manageLeaveRequest', $leaveRequest);

        try {
            $leaveRequest = $this->leaveService->approveLeaveRequest($leaveRequest, $request->user());

            return response()->json([
                'message' => 'Leave request approved',
                'data' => $leaveRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject leave request (admin)
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $leaveRequest->employee);
        $this->authorize('manageLeaveRequest', $leaveRequest);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $leaveRequest = $this->leaveService->rejectLeaveRequest(
                $leaveRequest,
                $request->user(),
                $request->rejection_reason
            );

            return response()->json([
                'message' => 'Leave request rejected',
                'data' => $leaveRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get employee leave balance (admin)
     */
    public function employeeBalance(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $year = $request->input('year', now()->year);
        $balance = $this->leaveService->getLeaveBalance($employee, $year);

        return response()->json(['data' => $balance]);
    }

    /**
     * Initialize leave balances for employee (admin)
     */
    public function initializeBalance(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $year = $request->input('year', now()->year);
        $balances = $this->leaveService->initializeLeaveBalances($employee, $year);

        return response()->json([
            'message' => 'Leave balances initialized',
            'data' => $balances,
        ]);
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
