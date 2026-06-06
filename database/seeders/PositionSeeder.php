<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        $dept = fn (string $name) => Department::where('company_id', $company->id)
            ->where('slug', Str::slug($name))
            ->firstOrFail();

        // ── Executive Office ──────────────────────────────────────
        $executive = $dept('Executive Office');

        $ceo = Position::firstOrCreate(
            ['department_id' => $executive->id, 'position_name' => 'Chief Executive Officer'],
            ['is_active' => true],
        );
        $coo = Position::firstOrCreate(
            ['department_id' => $executive->id, 'position_name' => 'Chief Operating Officer'],
            ['reports_to_position_id' => $ceo->id, 'is_active' => true],
        );
        $cfo = Position::firstOrCreate(
            ['department_id' => $executive->id, 'position_name' => 'Chief Financial Officer'],
            ['reports_to_position_id' => $ceo->id, 'is_active' => true],
        );
        $cto = Position::firstOrCreate(
            ['department_id' => $executive->id, 'position_name' => 'Chief Technology Officer'],
            ['reports_to_position_id' => $ceo->id, 'is_active' => true],
        );

        // ── Human Resources ───────────────────────────────────────
        $hr = $dept('Human Resources');

        $hrManager = Position::firstOrCreate(
            ['department_id' => $hr->id, 'position_name' => 'HR Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $hr->id, 'position_name' => 'HR Officer'],
            ['reports_to_position_id' => $hrManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $hr->id, 'position_name' => 'Recruitment Specialist'],
            ['reports_to_position_id' => $hrManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $hr->id, 'position_name' => 'Payroll Officer'],
            ['reports_to_position_id' => $hrManager->id, 'is_active' => true],
        );

        // ── Finance ───────────────────────────────────────────────
        $finance = $dept('Finance');

        $financeManager = Position::firstOrCreate(
            ['department_id' => $finance->id, 'position_name' => 'Finance Manager'],
            ['reports_to_position_id' => $cfo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $finance->id, 'position_name' => 'Senior Accountant'],
            ['reports_to_position_id' => $financeManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $finance->id, 'position_name' => 'Accountant'],
            ['reports_to_position_id' => $financeManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $finance->id, 'position_name' => 'Bookkeeper'],
            ['reports_to_position_id' => $financeManager->id, 'is_active' => true],
        );

        // ── Information Technology ────────────────────────────────
        $it = $dept('Information Technology');

        $itManager = Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'IT Manager'],
            ['reports_to_position_id' => $cto->id, 'is_active' => true],
        );
        $seniorDev = Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'Senior Developer'],
            ['reports_to_position_id' => $itManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'Junior Developer'],
            ['reports_to_position_id' => $seniorDev->id, 'is_active' => true],
        );
        $qaLead = Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'QA Lead'],
            ['reports_to_position_id' => $itManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'QA Analyst'],
            ['reports_to_position_id' => $qaLead->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $it->id, 'position_name' => 'Systems Administrator'],
            ['reports_to_position_id' => $itManager->id, 'is_active' => true],
        );

        // ── Operations ────────────────────────────────────────────
        $ops = $dept('Operations');

        $opsManager = Position::firstOrCreate(
            ['department_id' => $ops->id, 'position_name' => 'Operations Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        $opsSupervisor = Position::firstOrCreate(
            ['department_id' => $ops->id, 'position_name' => 'Operations Supervisor'],
            ['reports_to_position_id' => $opsManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $ops->id, 'position_name' => 'Operations Associate'],
            ['reports_to_position_id' => $opsSupervisor->id, 'is_active' => true],
        );

        // ── Sales ─────────────────────────────────────────────────
        $sales = $dept('Sales');

        $salesManager = Position::firstOrCreate(
            ['department_id' => $sales->id, 'position_name' => 'Sales Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $sales->id, 'position_name' => 'Senior Sales Executive'],
            ['reports_to_position_id' => $salesManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $sales->id, 'position_name' => 'Sales Executive'],
            ['reports_to_position_id' => $salesManager->id, 'is_active' => true],
        );

        // ── Marketing ─────────────────────────────────────────────
        $marketing = $dept('Marketing');

        $marketingManager = Position::firstOrCreate(
            ['department_id' => $marketing->id, 'position_name' => 'Marketing Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $marketing->id, 'position_name' => 'Marketing Specialist'],
            ['reports_to_position_id' => $marketingManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $marketing->id, 'position_name' => 'Graphic Designer'],
            ['reports_to_position_id' => $marketingManager->id, 'is_active' => true],
        );

        // ── Customer Service ──────────────────────────────────────
        $cs = $dept('Customer Service');

        $csManager = Position::firstOrCreate(
            ['department_id' => $cs->id, 'position_name' => 'Customer Service Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $cs->id, 'position_name' => 'Customer Service Supervisor'],
            ['reports_to_position_id' => $csManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $cs->id, 'position_name' => 'Customer Service Representative'],
            ['reports_to_position_id' => $csManager->id, 'is_active' => true],
        );

        // ── Legal & Compliance ────────────────────────────────────
        $legal = $dept('Legal & Compliance');

        $legalManager = Position::firstOrCreate(
            ['department_id' => $legal->id, 'position_name' => 'Legal & Compliance Manager'],
            ['reports_to_position_id' => $ceo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $legal->id, 'position_name' => 'Legal Officer'],
            ['reports_to_position_id' => $legalManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $legal->id, 'position_name' => 'Compliance Officer'],
            ['reports_to_position_id' => $legalManager->id, 'is_active' => true],
        );

        // ── Administration ────────────────────────────────────────
        $admin = $dept('Administration');

        $adminManager = Position::firstOrCreate(
            ['department_id' => $admin->id, 'position_name' => 'Administrative Manager'],
            ['reports_to_position_id' => $coo->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $admin->id, 'position_name' => 'Administrative Assistant'],
            ['reports_to_position_id' => $adminManager->id, 'is_active' => true],
        );
        Position::firstOrCreate(
            ['department_id' => $admin->id, 'position_name' => 'Receptionist'],
            ['reports_to_position_id' => $adminManager->id, 'is_active' => true],
        );
    }
}
