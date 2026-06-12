<?php

namespace App\Modules\Core\Imports;

use App\Models\User;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\Position;
use App\Modules\Core\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array<string, mixed>> */
    private array $results = [];

    /** @var string[] */
    private array $usedUsernames = [];

    public function __construct(private readonly int $companyId) {}

    public function collection(Collection $rows): void
    {
        $employeeRole = Role::where('slug', 'employee')->first();

        // Prefill used usernames from existing users in this company
        $this->usedUsernames = User::whereHas('employee', fn ($q) => $q->where('company_id', $this->companyId))
            ->pluck('username')
            ->filter()
            ->values()
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // 1-based, offset by heading row
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $lastName = trim((string) ($row['last_name'] ?? ''));
            $email = trim(strtolower((string) ($row['email'] ?? '')));

            if ($firstName === '' || $lastName === '' || $email === '') {
                $this->results[] = [
                    'row' => $rowNumber,
                    'name' => trim("{$firstName} {$lastName}") ?: "(row {$rowNumber})",
                    'username' => null,
                    'temp_password' => null,
                    'status' => 'skipped',
                    'error' => 'First Name, Last Name, and Email are required.',
                ];

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'name' => "{$firstName} {$lastName}",
                    'username' => null,
                    'temp_password' => null,
                    'status' => 'skipped',
                    'error' => "Invalid email: {$email}",
                ];

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'name' => "{$firstName} {$lastName}",
                    'username' => null,
                    'temp_password' => null,
                    'status' => 'skipped',
                    'error' => "Email already in use: {$email}",
                ];

                continue;
            }

            try {
                $username = $this->generateUsername($firstName, $lastName);
                $tempPassword = Str::random(8).'@1';

                DB::transaction(function () use ($firstName, $lastName, $email, $username, $tempPassword, $employeeRole, $row): void {
                    $user = User::create([
                        'name' => "{$firstName} {$lastName}",
                        'username' => $username,
                        'email' => $email,
                        'password' => Hash::make($tempPassword),
                        'company_id' => $this->companyId,
                        'email_verified_at' => now(),
                        'must_change_password' => true,
                    ]);

                    if ($employeeRole) {
                        $user->roles()->attach($employeeRole->id);
                    }

                    $departmentId = $this->resolveDepartmentId((string) ($row['department'] ?? ''));
                    $positionId = $this->resolvePositionId((string) ($row['position'] ?? ''), $departmentId);
                    $employeeId = trim((string) ($row['employee_id'] ?? ''));

                    if ($employeeId === '') {
                        $employeeId = $this->generateEmployeeId();
                    }

                    Employee::create([
                        'user_id' => $user->id,
                        'company_id' => $this->companyId,
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'employee_id' => $employeeId,
                        'first_name' => $firstName,
                        'middle_name' => trim((string) ($row['middle_name'] ?? '')),
                        'last_name' => $lastName,
                        'email' => $email,
                        'date_of_birth' => $this->parseDate((string) ($row['date_of_birth'] ?? '')),
                        'gender' => strtolower(trim((string) ($row['gender'] ?? ''))),
                        'phone' => trim((string) ($row['phone'] ?? '')),
                        'employment_status' => strtolower(trim((string) ($row['employment_status'] ?? 'active'))) ?: 'active',
                        'employment_type' => str_replace(' ', '_', strtolower(trim((string) ($row['employment_type'] ?? 'full_time')))) ?: 'full_time',
                        'salary' => is_numeric($row['salary'] ?? '') ? (float) $row['salary'] : null,
                        'salary_type' => strtolower(trim((string) ($row['salary_type'] ?? 'monthly'))) ?: 'monthly',
                        'tin' => trim((string) ($row['tin'] ?? '')),
                        'sss_number' => trim((string) ($row['sss_number'] ?? '')),
                        'philhealth_number' => trim((string) ($row['philhealth_number'] ?? '')),
                        'pagibig_number' => trim((string) ($row['pagibig_number'] ?? '')),
                        'is_active' => true,
                        'date_hired' => now()->toDateString(),
                    ]);
                });

                $this->results[] = [
                    'row' => $rowNumber,
                    'name' => "{$firstName} {$lastName}",
                    'username' => $username,
                    'temp_password' => $tempPassword,
                    'status' => 'created',
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $this->results[] = [
                    'row' => $rowNumber,
                    'name' => "{$firstName} {$lastName}",
                    'username' => null,
                    'temp_password' => null,
                    'status' => 'skipped',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function getResults(): array
    {
        return $this->results;
    }

    private function generateUsername(string $firstName, string $lastName): string
    {
        $firstInitial = strtolower(mb_substr($firstName, 0, 1));
        $lastNameSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastName));
        $base = $firstInitial.'.'.$lastNameSlug;
        $username = $base;
        $counter = 2;

        while (
            in_array($username, $this->usedUsernames) ||
            User::where('username', $username)->exists()
        ) {
            $username = $base.$counter;
            $counter++;
        }

        $this->usedUsernames[] = $username;

        return $username;
    }

    private function resolveDepartmentId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return Department::where('company_id', $this->companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->value('id');
    }

    private function resolvePositionId(string $name, ?int $departmentId): ?int
    {
        if ($name === '') {
            return null;
        }

        $query = Position::whereHas('department', fn ($q) => $q->where('company_id', $this->companyId))
            ->whereRaw('LOWER(position_name) = ?', [strtolower($name)]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->value('id');
    }

    private function generateEmployeeId(): string
    {
        $last = Employee::where('company_id', $this->companyId)
            ->where('employee_id', 'like', 'EMP-%')
            ->orderByRaw('CAST(SUBSTRING(employee_id, 5) AS UNSIGNED) DESC')
            ->value('employee_id');

        $next = $last ? ((int) substr($last, 4)) + 1 : 1;

        return sprintf('EMP-%04d', $next);
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
