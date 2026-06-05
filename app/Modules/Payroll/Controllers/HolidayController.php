<?php

namespace App\Modules\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\HolidayRequest;
use App\Modules\Payroll\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(): Response
    {
        $this->authorize('manageHolidays', Holiday::class);

        $companyId = request()->user()->employee?->company_id;

        $holidays = Holiday::where(function ($q) use ($companyId): void {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        })
            ->where('is_active', true)
            ->orderBy('date')
            ->get();

        return Inertia::render('Payroll/Settings/Holidays', [
            'holidays' => $holidays,
        ]);
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        $companyId = $request->user()->employee?->company_id;

        Holiday::create(array_merge($request->validated(), [
            'company_id' => $companyId,
            'is_active' => true,
        ]));

        return redirect()->back()->with('success', 'Holiday added successfully.');
    }

    public function update(HolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $this->authorize('update', $holiday);

        $holiday->update($request->validated());

        return redirect()->back()->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);

        $holiday->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Holiday removed successfully.');
    }
}
