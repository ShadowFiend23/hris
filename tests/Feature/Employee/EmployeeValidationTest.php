<?php

namespace Tests\Feature\Employee;

use App\Models\User;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithModuleAccess;

class EmployeeValidationTest extends TestCase
{
    use RefreshDatabase;
    use WithModuleAccess;

    protected User $user;

    protected Company $company;

    protected Department $department;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->setupModuleAccess($this->company->id);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'slug' => 'engineering',
            'is_active' => true,
        ]);
        $this->position = Position::create([
            'department_id' => $this->department->id,
            'position_name' => 'Software Engineer',
            'is_active' => true,
        ]);
    }

    public function test_first_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('first_name');
    }

    public function test_last_name_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('last_name');
    }

    public function test_email_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'invalid-email',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_date_hired_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('date_hired');
    }

    public function test_date_hired_cannot_be_future(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->addMonth()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('date_hired');
    }

    public function test_department_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_position_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
            ]);

        $response->assertSessionHasErrors('position_id');
    }

    public function test_gender_must_be_valid_option(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'gender' => 'invalid_gender',
            ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_employment_status_must_be_valid_option(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'employment_status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors('employment_status');
    }

    public function test_employment_type_must_be_valid_option(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'employment_type' => 'invalid_type',
            ]);

        $response->assertSessionHasErrors('employment_type');
    }

    public function test_salary_must_be_numeric(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'salary' => 'not-a-number',
            ]);

        $response->assertSessionHasErrors('salary');
    }

    public function test_salary_cannot_be_negative(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'salary' => -5000,
            ]);

        $response->assertSessionHasErrors('salary');
    }

    public function test_tin_must_be_valid_format(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'tin' => 'invalid!tin@format',
            ]);

        $response->assertSessionHasErrors('tin');
    }

    public function test_sss_number_must_be_valid_format(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'sss_number' => 'invalid!sss',
            ]);

        $response->assertSessionHasErrors('sss_number');
    }

    public function test_date_of_birth_must_be_in_past(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'date_of_birth' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_department_must_belong_to_user_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherDepartment = Department::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Dept',
            'slug' => 'other-dept',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/employees', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@example.com',
                'date_hired' => now()->format('Y-m-d'),
                'department_id' => $otherDepartment->id,
                'position_id' => $this->position->id,
            ]);

        $response->assertSessionHasErrors('department_id');
    }
}
