<?php

namespace Tests\Unit\Unit\Payroll;

use App\Modules\Payroll\Services\SSSContributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SSS service requires a DB connection for bracket lookups.
 * Returns 0.0 when no matching bracket exists (no data seeded).
 */
class SSSContributionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_share_returns_float(): void
    {
        $service = new SSSContributionService;
        $result = $service->computeEmployeeShare(15000);
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function test_employer_share_returns_non_negative(): void
    {
        $service = new SSSContributionService;
        $result = $service->computeEmployerShare(15000);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
