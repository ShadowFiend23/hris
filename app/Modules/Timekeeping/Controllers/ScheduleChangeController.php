<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timekeeping\Models\EmployeeSchedule;
use App\Modules\Timekeeping\Models\ScheduleChangeRequest;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleChangeController extends Controller
{
    /**
     * Get active shift templates available for requesting (employee).
     */
    public function availableTemplates(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $templates = ShiftTemplate::forCompany($companyId)
            ->active()
            ->get(['id', 'name', 'start_time', 'end_time']);

        return response()->json(['data' => $templates]);
    }

    /**
     * Get the authenticated employee's schedule change requests.
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $requests = ScheduleChangeRequest::forEmployee($employee->id)
            ->with(['requestedShiftTemplate:id,name,start_time,end_time'])
            ->orderByDesc('date')
            ->paginate($request->integer('per_page', 10));

        return response()->json($requests);
    }

    /**
     * Submit a new schedule change request.
     */
    public function store(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['error' => 'No employee profile found'], 404);
        }

        $data = $request->validate([
            'requested_shift_template_id' => ['required', 'integer', 'exists:shift_templates,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $shiftTemplate = ShiftTemplate::findOrFail($data['requested_shift_template_id']);

        if ((int) $shiftTemplate->company_id !== (int) $employee->company_id) {
            return response()->json(['error' => 'Invalid shift template'], 422);
        }

        $existing = ScheduleChangeRequest::forEmployee($employee->id)
            ->whereDate('date', $data['date'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['error' => 'You already have a pending schedule change request for this date'], 422);
        }

        $currentSchedule = EmployeeSchedule::forEmployee($employee->id)
            ->forDate($data['date'])
            ->first();

        $scheduleChangeRequest = ScheduleChangeRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'current_schedule_id' => $currentSchedule?->id,
            'requested_shift_template_id' => $data['requested_shift_template_id'],
            'date' => $data['date'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Schedule change request submitted successfully.',
            'data' => $scheduleChangeRequest->load('requestedShiftTemplate:id,name,start_time,end_time'),
        ], 201);
    }

    /**
     * Cancel a pending schedule change request (employee).
     */
    public function cancel(Request $request, ScheduleChangeRequest $scheduleChangeRequest): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee || (int) $scheduleChangeRequest->employee_id !== (int) $employee->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($scheduleChangeRequest->status !== 'pending') {
            return response()->json(['error' => 'Only pending requests can be cancelled'], 400);
        }

        $scheduleChangeRequest->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Schedule change request cancelled.']);
    }

    /**
     * Get all pending requests for the company (admin).
     */
    public function pending(Request $request): JsonResponse
    {
        $companyId = $request->user()->employee?->company_id;

        if (! $companyId) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $pending = ScheduleChangeRequest::forCompany($companyId)
            ->pending()
            ->with([
                'employee:id,first_name,last_name,employee_code',
                'requestedShiftTemplate:id,name,start_time,end_time',
                'currentSchedule.shiftTemplate:id,name',
            ])
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $pending]);
    }

    /**
     * Approve a schedule change request (admin).
     * Creates/updates the employee's schedule for that date.
     */
    public function approve(Request $request, ScheduleChangeRequest $scheduleChangeRequest): JsonResponse
    {
        $this->authorizeAdminAccess($request, $scheduleChangeRequest);

        if ($scheduleChangeRequest->status !== 'pending') {
            return response()->json(['error' => 'Only pending requests can be approved'], 400);
        }

        $template = $scheduleChangeRequest->requestedShiftTemplate;

        EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $scheduleChangeRequest->employee_id,
                'date' => $scheduleChangeRequest->date->toDateString(),
            ],
            [
                'shift_template_id' => $scheduleChangeRequest->requested_shift_template_id,
                'start_time' => $template->start_time,
                'end_time' => $template->end_time,
                'status' => 'scheduled',
            ]
        );

        $scheduleChangeRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Schedule change request approved and schedule updated.',
            'data' => $scheduleChangeRequest->fresh('employee', 'requestedShiftTemplate'),
        ]);
    }

    /**
     * Reject a schedule change request (admin).
     */
    public function reject(Request $request, ScheduleChangeRequest $scheduleChangeRequest): JsonResponse
    {
        $this->authorizeAdminAccess($request, $scheduleChangeRequest);

        if ($scheduleChangeRequest->status !== 'pending') {
            return response()->json(['error' => 'Only pending requests can be rejected'], 400);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $scheduleChangeRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => 'Schedule change request rejected.',
            'data' => $scheduleChangeRequest->fresh('employee', 'requestedShiftTemplate'),
        ]);
    }

    private function authorizeAdminAccess(Request $request, ScheduleChangeRequest $scheduleChangeRequest): void
    {
        $companyId = $request->user()->employee?->company_id;

        if ((int) $scheduleChangeRequest->company_id !== (int) $companyId) {
            abort(403, 'Unauthorized');
        }
    }
}
