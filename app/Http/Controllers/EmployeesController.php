<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Employees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeesController extends Controller
{
    public function index()
    {
        return Inertia::render('Personnel/Employees',[
            'employees' => Employees::select(DB::raw(
                    '*,CONCAT(
                        firstName,
                        IF(LENGTH(middleName),CONCAT(LEFT(middleName,0), ". "),NULL),
                        lastName
                    ) as fullName'))
                    ->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "firstName"         => "required",
            "lastName"          => "required",
            "employeeID"        => "required",
            "department"        => "required",
            "position"          => "required",
            "dateStart"         => "required",
            "status"            => "required",
            "employmentType"    => "required"
        ],[
            "employeeID.required" => "The employee id is required"
        ]);

        $tempValues = [
            "addressNo" => "",
            "addressStreet" => "",
            "addressProvince" => "",
            "addressCity" => "",
            "addressBarangay" => "",
            "phoneNumber" => "",
            "emailAddress" => "",
            "birthDate" => "1900-01-01",
            "photo" => "",
            "gender" => "",
            "maritalStatus" => "",
            "supervisor" => 0,
            "dateStart" => date("Y-m-d")
        ];

        $request->merge($tempValues);

        try {
            Employees::create($request->all());

            $response = [
                "type"      => "success",
                "message"   => "Successfully added an employee."
            ];

        } catch (\Exception $e) {
            Log::channel('store')->error(json_encode([
                "controller" => "EmployeesController",
                "params" => $e
            ]));
            $response = [
                "type"      => "error",
                "message"   => "Server Error. Please Try Again Later."
            ];
        }

        return Inertia::render('Personnel/Employees',[
            'employees' => Employees::select(DB::raw(
                    '*,CONCAT(
                        firstName,
                        IF(LENGTH(middleName),CONCAT(LEFT(middleName,0), ". "),NULL),
                        lastName
                    ) as fullName'))
                    ->get(),
            'response'  => $response
        ]);
    }

    public function edit(string $id)
    {

    }

    public function update(Request $request, string $id)
    {

    }

    public function destroy(string $id)
    {
        //
    }

    public function search(Request $request){
        $employees = [];
        if($request->exists("search")){
            $search = $request->search;
            $employees = Employees::select(DB::raw(
                    '*,CONCAT(
                        firstName,
                        IF(LENGTH(middleName),CONCAT(LEFT(middleName,0), ". "),NULL),
                        lastName
                    ) as fullName'))
                    ->where('firstName','LIKE',"%{$search}%")
                    ->orWhere('lastName','LIKE',"%{$search}%")
                    ->orWhere('middleName','LIKE',"%{$search}%")
                    ->orWhere('employeeID','LIKE',"%{$search}%")
                    ->get();
        }

        return response()->json($employees);
    }
}
