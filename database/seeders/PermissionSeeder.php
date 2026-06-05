<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, group: string}>
     */
    private array $permissions = [
        // HRIS / Employee management
        'hris.employees.view' => ['name' => 'View Employees', 'group' => 'hris'],
        'hris.employees.create' => ['name' => 'Create Employees', 'group' => 'hris'],
        'hris.employees.edit' => ['name' => 'Edit Employees', 'group' => 'hris'],
        'hris.employees.delete' => ['name' => 'Delete Employees', 'group' => 'hris'],

        // Timekeeping
        'timekeeping.view_own' => ['name' => 'View Own Timekeeping', 'group' => 'timekeeping'],
        'timekeeping.view_all' => ['name' => 'View All Timekeeping', 'group' => 'timekeeping'],
        'timekeeping.approve_attendance' => ['name' => 'Approve Attendance', 'group' => 'timekeeping'],
        'timekeeping.approve_leave' => ['name' => 'Approve Leave Requests', 'group' => 'timekeeping'],
        'timekeeping.approve_overtime' => ['name' => 'Approve Overtime Requests', 'group' => 'timekeeping'],
        'timekeeping.manage_shifts' => ['name' => 'Manage Shifts', 'group' => 'timekeeping'],
        'timekeeping.manage_biometric_sync' => ['name' => 'Manage Biometric Sync', 'group' => 'timekeeping'],

        // Payroll
        'payroll.view_own' => ['name' => 'View Own Payslip', 'group' => 'payroll'],
        'payroll.view_all' => ['name' => 'View All Payslips', 'group' => 'payroll'],
        'payroll.run' => ['name' => 'Run Payroll', 'group' => 'payroll'],
        'payroll.settings' => ['name' => 'Manage Payroll Settings', 'group' => 'payroll'],
        'payroll.loans' => ['name' => 'Manage Loans', 'group' => 'payroll'],
        'payroll.holidays' => ['name' => 'Manage Holidays', 'group' => 'payroll'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'admin' => [
            'hris.employees.view',
            'hris.employees.create',
            'hris.employees.edit',
            'hris.employees.delete',
            'timekeeping.view_own',
            'timekeeping.view_all',
            'timekeeping.approve_attendance',
            'timekeeping.approve_leave',
            'timekeeping.approve_overtime',
            'timekeeping.manage_shifts',
            'timekeeping.manage_biometric_sync',
            'payroll.view_own',
            'payroll.view_all',
            'payroll.run',
            'payroll.settings',
            'payroll.loans',
            'payroll.holidays',
        ],
        'manager' => [
            'hris.employees.view',
            'timekeeping.view_own',
            'timekeeping.view_all',
            'timekeeping.approve_attendance',
            'timekeeping.approve_leave',
            'timekeeping.approve_overtime',
            'timekeeping.manage_shifts',
            'payroll.view_own',
            'payroll.view_all',
        ],
        'employee' => [
            'timekeeping.view_own',
            'payroll.view_own',
        ],
    ];

    public function run(): void
    {
        $created = [];

        foreach ($this->permissions as $slug => $data) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'slug' => $slug,
                    'group' => $data['group'],
                ]
            );
            $created[$slug] = $permission;
        }

        foreach ($this->rolePermissions as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = collect($permissionSlugs)
                ->map(fn (string $slug) => $created[$slug]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
