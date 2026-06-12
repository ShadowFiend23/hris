<?php

namespace Tests\Unit\Unit\Payroll;

use App\Modules\Payroll\Services\WithholdingTaxService;
use Database\Seeders\ContributionBracketsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WithholdingTaxServiceTest extends TestCase
{
    use RefreshDatabase;

    private WithholdingTaxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContributionBracketsSeeder::class);
        $this->service = new WithholdingTaxService;
    }

    public function test_zero_tax_at_250k_threshold(): void
    {
        $this->assertSame(0.0, $this->service->computeAnnualTax(250000));
        $this->assertSame(0.0, $this->service->computeAnnualTax(0));
    }

    public function test_15_percent_bracket(): void
    {
        // Annual ₱300,000 → 15% of (₱300,000 - ₱250,000) = ₱7,500
        $this->assertSame(7500.0, $this->service->computeAnnualTax(300000));
    }

    public function test_20_percent_bracket(): void
    {
        // Annual ₱500,000 → ₱22,500 + 20% of (₱100,000) = ₱42,500
        $this->assertSame(42500.0, $this->service->computeAnnualTax(500000));
    }

    public function test_25_percent_bracket(): void
    {
        // Annual ₱1,000,000 → ₱102,500 + 25% of (₱200,000) = ₱152,500
        $this->assertSame(152500.0, $this->service->computeAnnualTax(1000000));
    }

    public function test_monthly_withholding_divides_annual_by_months(): void
    {
        // ₱25,000/month → annualized ₱300,000 → annual tax ₱7,500 → monthly ₱625
        $monthly = $this->service->computeMonthlyWithholding(25000, 12);
        $this->assertSame(625.0, $monthly);
    }

    public function test_employee_below_threshold_has_zero_withholding(): void
    {
        // ₱20,000/month = ₱240,000 annual < ₱250,000 threshold
        $monthly = $this->service->computeMonthlyWithholding(20000, 12);
        $this->assertSame(0.0, $monthly);
    }

    public function test_semi_monthly_table_bracket(): void
    {
        // Taxable ₱20,000 per cut-off → BIR semi-monthly bracket floor ₱16,667:
        // ₱937.50 + 20% of (₱20,000 - ₱16,667) = ₱937.50 + ₱666.60 = ₱1,604.10
        $tax = $this->service->computeForPeriod(20000, 'semi_monthly');
        $this->assertEqualsWithDelta(1604.10, $tax, 0.5);
    }

    public function test_semi_monthly_below_first_bracket_is_zero(): void
    {
        // ₱10,000 per cut-off is below the ₱10,417 floor → no withholding.
        $this->assertSame(0.0, $this->service->computeForPeriod(10000, 'semi_monthly'));
    }
}
