<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\License;
use App\Modules\Core\Models\Module;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'registration_number' => 'REG001',
            'address' => '123 St',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => '1000',
            'phone' => '123',
            'email' => 'test@company.com',
            'is_active' => true,
        ]);

        $license = License::create([
            'company_id' => $this->company->id,
            'license_key' => 'LIC-TEST-001',
            'type' => 'enterprise',
            'status' => 'active',
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'user_limit' => 999,
        ]);

        $module = Module::firstOrCreate(
            ['slug' => 'hris'],
            ['code' => 'hris', 'name' => 'HRIS', 'slug' => 'hris', 'description' => 'HRIS Module', 'is_active' => true]
        );
        $license->modules()->attach($module->id);

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'slug' => 'admin', 'is_system' => true]
        );

        Role::firstOrCreate(
            ['slug' => 'employee'],
            ['name' => 'Employee', 'slug' => 'employee', 'is_system' => true]
        );

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->admin->roles()->attach($adminRole->id);
    }

    private function makeExcelFile(array $rows, array $headers): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $col => $header) {
            $sheet->getCell([$col + 1, 1])->setValue($header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach (array_values($row) as $col => $value) {
                $sheet->getCell([$col + 1, $rowIndex + 2])->setValue($value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'import_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_admin_can_download_import_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('employees.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_valid_excel_creates_employees_and_users(): void
    {
        $headers = ['First Name', 'Last Name', 'Middle Name', 'Email', 'Date of Birth', 'Gender', 'Phone', 'Department', 'Position', 'Employment Type', 'Employment Status', 'Employee ID', 'Salary', 'Salary Type', 'TIN', 'SSS Number', 'PhilHealth Number', 'PagIBIG Number'];

        $rows = [
            ['Maria', 'Santos', '', 'maria.santos@company.com', '1990-01-15', 'female', '09171234567', '', '', '', '', '', '', '', '', '', '', ''],
            ['Juan', 'Dela Cruz', 'Jose', 'juan.delacruz@company.com', '1988-05-20', 'male', '09189876543', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        $file = $this->makeExcelFile($rows, $headers);

        $response = $this->actingAs($this->admin)->post(route('employees.import'), ['file' => $file]);

        $response->assertStatus(200);
        $results = $response->json('results');

        $this->assertCount(2, $results);
        $this->assertSame('created', $results[0]['status']);
        $this->assertSame('created', $results[1]['status']);
        $this->assertNotNull($results[0]['username']);
        $this->assertNotNull($results[0]['temp_password']);

        $this->assertDatabaseHas('users', ['email' => 'maria.santos@company.com', 'must_change_password' => true]);
        $this->assertDatabaseHas('employees', ['email' => 'maria.santos@company.com', 'company_id' => $this->company->id]);
        $this->assertEquals(2, Employee::where('company_id', $this->company->id)->count());
    }

    public function test_duplicate_email_is_skipped(): void
    {
        User::factory()->create(['email' => 'existing@company.com', 'company_id' => $this->company->id]);

        $headers = ['First Name', 'Last Name', 'Middle Name', 'Email', 'Date of Birth', 'Gender', 'Phone', 'Department', 'Position', 'Employment Type', 'Employment Status', 'Employee ID', 'Salary', 'Salary Type', 'TIN', 'SSS Number', 'PhilHealth Number', 'PagIBIG Number'];

        $rows = [
            ['Ana', 'Reyes', '', 'existing@company.com', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        $file = $this->makeExcelFile($rows, $headers);

        $response = $this->actingAs($this->admin)->post(route('employees.import'), ['file' => $file]);

        $response->assertStatus(200);
        $this->assertSame('skipped', $response->json('results.0.status'));
    }

    public function test_row_missing_required_fields_is_skipped(): void
    {
        $headers = ['First Name', 'Last Name', 'Middle Name', 'Email', 'Date of Birth', 'Gender', 'Phone', 'Department', 'Position', 'Employment Type', 'Employment Status', 'Employee ID', 'Salary', 'Salary Type', 'TIN', 'SSS Number', 'PhilHealth Number', 'PagIBIG Number'];

        $rows = [
            ['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        $file = $this->makeExcelFile($rows, $headers);

        $response = $this->actingAs($this->admin)->post(route('employees.import'), ['file' => $file]);

        $response->assertStatus(200);
        $this->assertSame('skipped', $response->json('results.0.status'));
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('employees.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->postJson(route('employees.import'), ['file' => $file]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('file');
    }

    public function test_imported_users_have_unique_usernames(): void
    {
        $headers = ['First Name', 'Last Name', 'Middle Name', 'Email', 'Date of Birth', 'Gender', 'Phone', 'Department', 'Position', 'Employment Type', 'Employment Status', 'Employee ID', 'Salary', 'Salary Type', 'TIN', 'SSS Number', 'PhilHealth Number', 'PagIBIG Number'];

        $rows = [
            ['Jose', 'Cruz', '', 'jose.cruz@company.com', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Josefa', 'Cruz', '', 'josefa.cruz@company.com', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ];

        $file = $this->makeExcelFile($rows, $headers);

        $response = $this->actingAs($this->admin)->post(route('employees.import'), ['file' => $file]);

        $response->assertStatus(200);
        $results = $response->json('results');

        $usernames = array_column($results, 'username');
        $this->assertCount(2, array_unique(array_filter($usernames)));
    }
}
