<?php

use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api/core')->group(function () {
    // Employee API routes
    Route::get('/employees', function (Request $request) {
        $companyId = $request->user()->company_id;
        $employeeService = app(\App\Modules\Core\Services\EmployeeService::class);

        $filters = [
            'search' => $request->input('search'),
            'department_id' => $request->input('department_id'),
            'position_id' => $request->input('position_id'),
            'employment_status' => $request->input('employment_status'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
        ];

        return response()->json($employeeService->getEmployeesByCompany($companyId, $filters));
    });

    Route::get('/employees/{employee}', function (\App\Modules\Core\Models\Employee $employee, Request $request) {
        if ($employee->company_id !== $request->user()->company_id) {
            abort(403);
        }

        return response()->json($employee->load(['department', 'position', 'company']));
    });

    Route::post('/employees', function (\App\Http\Requests\Employee\StoreEmployeeRequest $request) {
        $employeeService = app(\App\Modules\Core\Services\EmployeeService::class);
        $employee = $employeeService->createEmployee($request->validated());

        return response()->json($employee, 201);
    });

    Route::put('/employees/{employee}', function (\App\Http\Requests\Employee\UpdateEmployeeRequest $request, \App\Modules\Core\Models\Employee $employee) {
        if ($employee->company_id !== $request->user()->company_id) {
            abort(403);
        }
        $employeeService = app(\App\Modules\Core\Services\EmployeeService::class);
        $employee = $employeeService->updateEmployee($employee, $request->validated());

        return response()->json($employee);
    });

    Route::delete('/employees/{employee}', function (\App\Modules\Core\Models\Employee $employee, Request $request) {
        if ($employee->company_id !== $request->user()->company_id) {
            abort(403);
        }
        $employeeService = app(\App\Modules\Core\Services\EmployeeService::class);
        $employeeService->deleteEmployee($employee);

        return response()->json(['message' => 'Employee deleted successfully']);
    });

    // Restore soft-deleted employee
    Route::post('/employees/{id}/restore', function (int $id, Request $request) {
        $companyId = $request->user()->company_id;
        $employee = \App\Modules\Core\Models\Employee::withTrashed()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if (! $employee) {
            abort(404, 'Employee not found.');
        }

        if (! $employee->trashed()) {
            return response()->json(['message' => 'Employee is not deleted.'], 400);
        }

        $employeeService = app(\App\Modules\Core\Services\EmployeeService::class);
        $employeeService->restoreEmployee($employee);

        return response()->json(['message' => 'Employee restored successfully', 'employee' => $employee]);
    });

    // Department dropdown data
    Route::get('/departments', function (Request $request) {
        $companyId = $request->user()->company_id;

        return response()->json(
            Department::where('company_id', $companyId)
                ->where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    });

    // Position dropdown data
    Route::get('/positions', function (Request $request) {
        return response()->json(
            Position::where('is_active', true)
                ->select('id', 'position_name', 'department_id')
                ->orderBy('position_name')
                ->get()
        );
    });
});
