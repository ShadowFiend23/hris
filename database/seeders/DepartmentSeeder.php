<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'test-company')->firstOrFail();

        $departments = [
            ['name' => 'Executive Office', 'description' => 'Senior leadership and executive management'],
            ['name' => 'Human Resources', 'description' => 'People management, recruitment, and employee relations'],
            ['name' => 'Finance', 'description' => 'Financial planning, accounting, and treasury'],
            ['name' => 'Information Technology', 'description' => 'Software development, infrastructure, and IT support'],
            ['name' => 'Operations', 'description' => 'Day-to-day operational activities and process management'],
            ['name' => 'Sales', 'description' => 'Sales strategy, client acquisition, and revenue generation'],
            ['name' => 'Marketing', 'description' => 'Brand management, campaigns, and market research'],
            ['name' => 'Customer Service', 'description' => 'Client support, after-sales service, and customer success'],
            ['name' => 'Legal & Compliance', 'description' => 'Legal counsel, regulatory compliance, and risk management'],
            ['name' => 'Administration', 'description' => 'General administrative support and office management'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['company_id' => $company->id, 'slug' => Str::slug($dept['name'])],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
