<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'code' => 'hris',
                'name' => 'HRIS / Core',
                'description' => 'Human Resources Information System and core functionality',
                'icon' => 'Users',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'timekeeping',
                'name' => 'Timekeeping',
                'description' => 'Attendance and time tracking module',
                'icon' => 'Clock',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'payroll',
                'name' => 'Payroll',
                'description' => 'Payroll management and salary processing',
                'icon' => 'DollarSign',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['code' => $module['code']],
                $module
            );
        }
    }
}
