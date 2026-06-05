<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayrollSettingRequest;
use App\Modules\Payroll\Models\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PayrollSettingsController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAnySettings', PayrollSetting::class);

        $user = request()->user()->load('employee');
        $companyId = $user->employee?->company_id;

        $setting = PayrollSetting::where('company_id', $companyId)->first();

        return Inertia::render('Payroll/Settings/PayrollSettings', [
            'setting' => $setting,
        ]);
    }

    public function store(PayrollSettingRequest $request): RedirectResponse
    {
        $companyId = $request->user()->employee?->company_id;

        PayrollSetting::updateOrCreate(
            ['company_id' => $companyId],
            array_merge($request->validated(), ['company_id' => $companyId])
        );

        return redirect()->back()->with('success', 'Payroll settings saved successfully.');
    }
}
