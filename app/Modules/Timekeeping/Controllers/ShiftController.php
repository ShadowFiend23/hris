<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\ShiftSwapRequest;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use App\Modules\Timekeeping\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private ShiftService $shiftService
    ) {}

    /**
     * Get shift templates
     */
    public function templates(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $templates = $this->shiftService->getShiftTemplates($companyId);

        return response()->json(['data' => $templates]);
    }

    /**
     * Create shift template (admin)
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'duration_hours' => 'nullable|numeric|min:1|max:24',
            'break_duration' => 'nullable|integer|min:0|max:180',
            'break_start_time' => 'nullable|date_format:H:i:s',
            'break_end_time' => 'nullable|date_format:H:i:s|after:break_start_time',
        ]);

        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $template = $this->shiftService->createShiftTemplate($companyId, $request->all());

        return response()->json([
            'message' => 'Shift template created',
            'data' => $template,
        ], 201);
    }

    /**
     * Get current user's schedule
     */
    public function mySchedule(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $startDate = $request->input('start_date', now()->startOfWeek()->toDateString());
        $endDate = $request->input('end_date', now()->endOfWeek()->toDateString());

        $schedule = $this->shiftService->getEmployeeSchedule(
            $employee,
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );

        return response()->json(['data' => $schedule]);
    }

    /**
     * Get weekly schedule
     */
    public function weekly(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $weekStart = $request->input('week_start')
            ? Carbon::parse($request->input('week_start'))->startOfWeek()
            : now()->startOfWeek();

        $schedule = $this->shiftService->getWeeklySchedule($employee, $weekStart);

        return response()->json([
            'data' => $schedule,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekStart->copy()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Assign shift to employee (admin)
     */
    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $this->authorizeCompanyAccess($request, $employee);

        $shift = ShiftTemplate::findOrFail($request->shift_template_id);

        $schedule = $this->shiftService->assignShift($employee, $shift, Carbon::parse($request->date));

        return response()->json([
            'message' => 'Shift assigned',
            'data' => $schedule->load('shiftTemplate'),
        ]);
    }

    /**
     * Bulk assign shifts (admin)
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $companyId = $request->user()->employee?->company_id;
        $shift = ShiftTemplate::findOrFail($request->shift_template_id);

        // Verify all employees belong to the same company
        $employees = Employee::whereIn('id', $request->employee_ids)
            ->where('company_id', $companyId)
            ->get();

        if ($employees->count() !== count($request->employee_ids)) {
            return response()->json(['error' => 'Some employees are not in your company'], 403);
        }

        $schedules = $this->shiftService->bulkAssignShifts(
            $request->employee_ids,
            $shift,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json([
            'message' => 'Shifts assigned to '.$employees->count().' employees',
            'count' => $schedules->count(),
        ]);
    }

    /**
     * Request shift swap
     */
    public function requestSwap(Request $request): JsonResponse
    {
        $request->validate([
            'my_schedule_id' => 'required|exists:employee_schedules,id',
            'target_schedule_id' => 'required|exists:employee_schedules,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $mySchedule = EmployeeSchedule::findOrFail($request->my_schedule_id);
        $targetSchedule = EmployeeSchedule::findOrFail($request->target_schedule_id);

        // Verify ownership
        if ((int) $mySchedule->employee_id !== (int) $employee->id) {
            return response()->json(['error' => 'This is not your schedule'], 403);
        }

        $targetEmployee = Employee::findOrFail($targetSchedule->employee_id);

        // Verify same company
        if ((int) $employee->company_id !== (int) $targetEmployee->company_id) {
            return response()->json(['error' => 'Cannot swap shifts with employees from different companies'], 403);
        }

        try {
            $swapRequest = $this->shiftService->requestShiftSwap(
                $employee,
                $mySchedule,
                $targetEmployee,
                $targetSchedule,
                $request->reason
            );

            return response()->json([
                'message' => 'Shift swap request submitted',
                'data' => $swapRequest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get pending swap requests (admin)
     */
    public function pendingSwaps(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $pending = $this->shiftService->getPendingSwapRequests($companyId);

        return response()->json(['data' => $pending]);
    }

    /**
     * Approve shift swap (admin)
     */
    public function approveSwap(Request $request, ShiftSwapRequest $swapRequest): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $swapRequest->requester);
        $this->authorize('manageShiftSwap', $swapRequest);

        try {
            $swapRequest = $this->shiftService->approveShiftSwap($swapRequest, $request->user());

            return response()->json([
                'message' => 'Shift swap approved',
                'data' => $swapRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject shift swap (admin)
     */
    public function rejectSwap(Request $request, ShiftSwapRequest $swapRequest): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $swapRequest->requester);
        $this->authorize('manageShiftSwap', $swapRequest);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $swapRequest = $this->shiftService->rejectShiftSwap(
                $swapRequest,
                $request->user(),
                $request->rejection_reason
            );

            return response()->json([
                'message' => 'Shift swap rejected',
                'data' => $swapRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get employee schedule (admin)
     */
    public function employeeSchedule(Request $request, Employee $employee): JsonResponse
    {
        $this->authorizeCompanyAccess($request, $employee);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()->toDateString()));

        $schedule = $this->shiftService->getEmployeeSchedule($employee, $startDate, $endDate);

        return response()->json(['data' => $schedule]);
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
