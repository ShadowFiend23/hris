<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Employees;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentsController extends Controller
{

    public function index()
    {
        $department = Departments::departmentSelect();
        $employees = Employees::employeeSelect();

        return Inertia::render('Personnel/Departments', [
            "departments" => Departments::withEmployees()->get(array_merge($department, $employees))
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:departments,name',
            'positions' => 'required'
        ]);

        $request->merge([
            "departmentHead" => $request->input('departmentHead')['id'],
            "positions" => json_encode($request->input('positions'))
        ]);

        try {

            Departments::create($request->all());

            $response = [
                "type"      => "success",
                "message"   => "Successfully added a department."
            ];

        } catch (\Exception $e) {
            Log::channel('store')->error(json_encode([
                "controller" => "DepartmentsController",
                "params" => $e
            ]));

            $response = [
                "type"      => "error",
                "message"   => $e->getMessage()
            ];
        }

        $department = Departments::departmentSelect();
        $employees = Employees::employeeSelect();

        return Inertia::render('Personnel/Departments',[
            'departments' => Departments::withEmployees()->get(array_merge($department, $employees)),
            'response' => $response
        ]);
    }

    public function show(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {

    }
}
