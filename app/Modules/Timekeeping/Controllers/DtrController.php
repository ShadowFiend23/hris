<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Services\DtrService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DtrController extends Controller
{
    public function __construct(
        private DtrService $dtrService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            abort(403, 'No employee profile found.');
        }

        // Managers and admins see themselves + direct reports
        $employees = $this->getAccessibleEmployees($user, $employee);

        return Inertia::render('Timekeeping/Dtr', [
            'employees' => $employees->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->full_name,
            ])->values(),
            'currentYear' => now()->year,
            'currentMonth' => now()->month,
            'myEmployeeId' => $employee->id,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $requestedEmployee = Employee::findOrFail($validated['employee_id']);

        if (! $this->canAccessDtr($user, $requestedEmployee)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $this->dtrService->generateDtrData(
            $requestedEmployee,
            (int) $validated['year'],
            (int) $validated['month']
        );

        // Remove model instance from JSON response
        unset($data['employee']);

        return response()->json($data);
    }

    public function download(Request $request, Employee $employee): HttpResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->canAccessDtr($user, $employee)) {
            abort(403);
        }

        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $data = $this->dtrService->generateDtrData($employee, $year, $month);

        $filename = 'DTR_'.str_replace(' ', '_', $employee->full_name).'_'.$data['month_name'].'.pdf';

        return Pdf::loadView('timekeeping.dtr', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * Check whether the given user may view DTR for the target employee.
     */
    private function canAccessDtr(User $user, Employee $targetEmployee): bool
    {
        if ($user->hasRole('admin') && (int) $user->company_id === (int) $targetEmployee->company_id) {
            return true;
        }

        $myEmployee = $user->employee;

        if (! $myEmployee) {
            return false;
        }

        // Own DTR
        if ($myEmployee->id === $targetEmployee->id) {
            return true;
        }

        // Manager: direct reports only
        if ((int) $targetEmployee->supervisor_id === (int) $myEmployee->id) {
            return true;
        }

        return false;
    }

    /**
     * Get all employees the user is allowed to pull DTR for.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    private function getAccessibleEmployees(User $user, Employee $myEmployee): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasRole('admin')) {
            return Employee::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get();
        }

        $directReports = Employee::where('supervisor_id', $myEmployee->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        return $directReports->prepend($myEmployee);
    }
}
