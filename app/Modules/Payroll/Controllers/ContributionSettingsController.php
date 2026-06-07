<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payroll\Models\ContributionBracket;
use App\Modules\Payroll\Models\PayrollSetting;
use App\Modules\Payroll\Services\PagibigContributionService;
use App\Modules\Payroll\Services\PhilHealthContributionService;
use App\Modules\Payroll\Services\WithholdingTaxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContributionSettingsController extends Controller
{
    public function __construct(
        private readonly PhilHealthContributionService $philhealth,
        private readonly PagibigContributionService $pagibig,
        private readonly WithholdingTaxService $tax,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $sss = ContributionBracket::where('type', 'sss')
            ->where('is_active', true)
            ->orderBy('min_salary')
            ->get();

        return Inertia::render('HRSettings/ContributionSettings', [
            'sss' => $sss,
            'philhealth' => $this->philhealth->getSetting(),
            'pagibig' => $this->pagibig->getBrackets(),
            'tax' => $this->tax->getBrackets(),
        ]);
    }

    public function updatePhilhealth(Request $request): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $data = $request->validate([
            'employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'employer_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'min_contribution' => ['required', 'numeric', 'min:0'],
            'max_contribution' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        ContributionBracket::where('type', 'philhealth')->where('is_active', true)->update(['is_active' => false]);

        ContributionBracket::create(array_merge($data, [
            'type' => 'philhealth',
            'effective_date' => now()->toDateString(),
            'min_salary' => 0,
            'max_salary' => null,
            'is_active' => true,
        ]));

        return redirect()->route('app-settings.contribution.index')
            ->with('success', 'PhilHealth contribution settings updated.');
    }

    public function updatePagibig(Request $request): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $data = $request->validate([
            'brackets' => ['required', 'array', 'min:1'],
            'brackets.*.min_salary' => ['required', 'numeric', 'min:0'],
            'brackets.*.max_salary' => ['nullable', 'numeric'],
            'brackets.*.employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'brackets.*.employer_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'brackets.*.max_contribution' => ['nullable', 'numeric', 'min:0'],
            'brackets.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        ContributionBracket::where('type', 'pagibig')->where('is_active', true)->update(['is_active' => false]);

        foreach ($data['brackets'] as $b) {
            ContributionBracket::create(array_merge($b, [
                'type' => 'pagibig',
                'effective_date' => now()->toDateString(),
                'is_active' => true,
            ]));
        }

        return redirect()->route('app-settings.contribution.index')
            ->with('success', 'Pag-IBIG contribution settings updated.');
    }

    public function storeBracket(Request $request): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $type = $request->validate(['type' => ['required', 'in:sss,tax']])['type'];

        if ($type === 'sss') {
            $data = $request->validate([
                'min_salary' => ['required', 'numeric', 'min:0'],
                'max_salary' => ['nullable', 'numeric'],
                'employee_amount' => ['nullable', 'numeric', 'min:0'],
                'employer_amount' => ['nullable', 'numeric', 'min:0'],
                'employee_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'employer_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);
        } else {
            $data = $request->validate([
                'min_salary' => ['required', 'numeric', 'min:0'],
                'employee_amount' => ['required', 'numeric', 'min:0'],
                'employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);
        }

        ContributionBracket::create(array_merge($data, [
            'type' => $type,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ]));

        return redirect()->route('app-settings.contribution.index')
            ->with('success', strtoupper($type).' bracket added.');
    }

    public function updateBracket(Request $request, ContributionBracket $bracket): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        if ($bracket->type === 'sss') {
            $data = $request->validate([
                'min_salary' => ['required', 'numeric', 'min:0'],
                'max_salary' => ['nullable', 'numeric'],
                'employee_amount' => ['nullable', 'numeric', 'min:0'],
                'employer_amount' => ['nullable', 'numeric', 'min:0'],
                'employee_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'employer_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);
        } elseif ($bracket->type === 'pagibig') {
            $data = $request->validate([
                'min_salary' => ['required', 'numeric', 'min:0'],
                'max_salary' => ['nullable', 'numeric'],
                'employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'employer_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'max_contribution' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);
        } elseif ($bracket->type === 'tax') {
            $data = $request->validate([
                'min_salary' => ['required', 'numeric', 'min:0'],
                'employee_amount' => ['required', 'numeric', 'min:0'],
                'employee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
                'notes' => ['nullable', 'string', 'max:255'],
            ]);
        } else {
            abort(422);
        }

        $bracket->update($data);

        return redirect()->route('app-settings.contribution.index')
            ->with('success', strtoupper($bracket->type).' bracket updated.');
    }

    public function destroyBracket(ContributionBracket $bracket): RedirectResponse
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $bracket->delete();

        return redirect()->route('app-settings.contribution.index')
            ->with('success', strtoupper($bracket->type).' bracket removed.');
    }
}
