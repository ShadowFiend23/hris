<?php

namespace App\Modules\Core\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Employee;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // $user = Auth::user();

        // $employee = $user->employee;
        // $company = $user->company;

        // $stats = [
        //     'total_employees' => Employee::where('company_id', $user->company_id)
        //         ->where('employment_status', 'active')
        //         ->count(),
        //     'total_departments' => $company?->departments()->count() ?? 0,
        //     'total_users' => User::where('company_id', $user->company_id)->count(),
        // ];

        // $recentActivities = AuditLog::with('user')
        //     ->whereIn('user_id', User::where('company_id', $user->company_id)->pluck('id'))
        //     ->latest()
        //     ->limit(10)
        //     ->get()
        //     ->map(fn ($log) => [
        //         'id' => $log->id,
        //         'action' => $log->action,
        //         'user_name' => $log->user?->name,
        //         'subject_type' => $log->subject_type,
        //         'created_at' => $log->created_at,
        //     ]);

        return Inertia::render('Dashboard', []);

        // return Inertia::render('Dashboard', [
        //     'employee' => $employee ? [
        //         'id' => $employee->id,
        //         'first_name' => $employee->first_name,
        //         'last_name' => $employee->last_name,
        //         'email' => $employee->email,
        //         'position' => $employee->position,
        //         'department_name' => $employee->department?->name,
        //     ] : null,
        //     'company' => $company ? [
        //         'id' => $company->id,
        //         'name' => $company->name,
        //     ] : null,
        //     'stats' => $stats,
        //     'recentActivities' => $recentActivities,
        // ]);
    }
}
