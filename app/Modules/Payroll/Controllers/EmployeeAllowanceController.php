<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\EmployeeAllowanceRequest;
use App\Modules\Core\Models\Employee;
use App\Modules\Payroll\Models\AllowanceType;
use App\Modules\Payroll\Models\EmployeeAllowance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeAllowanceController extends Controller
{
    public function index(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $allowances = EmployeeAllowance::where('employee_id', $employee->id)
            ->orderBy('name')
            ->get()
            ->map(fn (EmployeeAllowance $a) => [
                'id' => $a->id,
                'allowance_type_id' => $a->allowance_type_id,
                'type' => $a->type,
                'name' => $a->name,
                'amount' => (float) $a->amount,
                'is_taxable' => (bool) $a->is_taxable,
                'frequency' => $a->frequency,
                'is_active' => (bool) $a->is_active,
            ]);

        return response()->json($allowances);
    }

    public function store(EmployeeAllowanceRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $allowanceType = AllowanceType::findOrFail($request->input('allowance_type_id'));

        $allowance = EmployeeAllowance::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'allowance_type_id' => $allowanceType->id,
            'type' => $allowanceType->code,
            'name' => $request->input('name') ?: $allowanceType->name,
            'amount' => $request->input('amount'),
            'is_taxable' => $allowanceType->is_taxable,
            'frequency' => $request->input('frequency'),
            'is_active' => $request->input('is_active'),
        ]);

        return response()->json([
            'id' => $allowance->id,
            'allowance_type_id' => $allowance->allowance_type_id,
            'type' => $allowance->type,
            'name' => $allowance->name,
            'amount' => (float) $allowance->amount,
            'is_taxable' => (bool) $allowance->is_taxable,
            'frequency' => $allowance->frequency,
            'is_active' => (bool) $allowance->is_active,
        ], 201);
    }

    public function update(EmployeeAllowanceRequest $request, Employee $employee, EmployeeAllowance $allowance): JsonResponse
    {
        $this->authorize('update', $employee);
        abort_if((int) $allowance->employee_id !== (int) $employee->id, 404);

        $allowanceType = AllowanceType::findOrFail($request->input('allowance_type_id'));

        $allowance->update([
            'allowance_type_id' => $allowanceType->id,
            'type' => $allowanceType->code,
            'name' => $request->input('name') ?: $allowanceType->name,
            'amount' => $request->input('amount'),
            'is_taxable' => $allowanceType->is_taxable,
            'frequency' => $request->input('frequency'),
            'is_active' => $request->input('is_active'),
        ]);

        return response()->json([
            'id' => $allowance->id,
            'allowance_type_id' => $allowance->allowance_type_id,
            'type' => $allowance->type,
            'name' => $allowance->name,
            'amount' => (float) $allowance->amount,
            'is_taxable' => (bool) $allowance->is_taxable,
            'frequency' => $allowance->frequency,
            'is_active' => (bool) $allowance->is_active,
        ]);
    }

    public function destroy(Request $request, Employee $employee, EmployeeAllowance $allowance): JsonResponse
    {
        $this->authorize('update', $employee);
        abort_if((int) $allowance->employee_id !== (int) $employee->id, 404);

        $allowance->delete();

        return response()->json(['message' => 'Allowance deleted.']);
    }
}
