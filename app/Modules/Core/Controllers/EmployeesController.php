<?php

namespace App\Modules\Core\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Employee;

class EmployeesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Employees/Employees', []);
    }
}
