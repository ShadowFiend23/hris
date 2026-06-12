<?php

namespace Tests\Unit\Unit\Payroll;

use App\Modules\Payroll\Services\PhilHealthContributionService;
use Database\Seeders\ContributionBracketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhilHealthContributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PhilHealthContributionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContributionBracketsSeeder::class);
        $this->service = new PhilHealthContributionService;
    }

    public function test_5_percent_of_salary(): void
    {
        // ₱20,000 salary: 5% = ₱1,000 total → ₱500 each side
        $this->assertSame(500.0, $this->service->computeEmployeeShare(20000));
        $this->assertSame(500.0, $this->service->computeEmployerShare(20000));
    }

    public function test_minimum_contribution_enforced(): void
    {
        // ₱5,000 salary: 5% = ₱250 < floor ₱500 → use floor ₱500 total
        $totalPremium = $this->service->computeTotalMonthlyPremium(5000);
        $this->assertSame(500.0, $totalPremium);
    }

    public function test_maximum_contribution_capped(): void
    {
        // ₱200,000 salary: 5% = ₱10,000 > cap ₱5,000 → use cap
        $totalPremium = $this->service->computeTotalMonthlyPremium(200000);
        $this->assertSame(5000.0, $totalPremium);
        $this->assertSame(2500.0, $this->service->computeEmployeeShare(200000));
    }

    public function test_semi_monthly_cutoff_is_half_monthly(): void
    {
        $monthly = $this->service->computeEmployeeShare(30000);
        $cutoff = $this->service->computeEmployeeSharePerCutoff(30000, 2);
        $this->assertEqualsWithDelta($monthly / 2, $cutoff, 0.01);
    }
}
