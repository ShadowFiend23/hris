<?php

namespace App\Modules\Timekeeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftTemplatesController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect(route('app-settings.timekeeping.index').'?sub=shifts');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration' => ['nullable', 'integer', 'min:0', 'max:180'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i', 'after:break_start_time'],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'swap_enabled' => ['boolean'],
        ]);

        $companyId = Auth::user()->employee?->company_id;

        ShiftTemplate::create(array_merge($data, [
            'company_id' => $companyId,
            'is_active' => true,
            'swap_enabled' => $data['swap_enabled'] ?? false,
        ]));

        return redirect(route('app-settings.timekeeping.index').'?sub=shifts')
            ->with('success', 'Shift template created successfully.');
    }

    public function update(Request $request, ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->authorizeTemplate($shiftTemplate);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_duration' => ['nullable', 'integer', 'min:0', 'max:180'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i', 'after:break_start_time'],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'is_active' => ['boolean'],
            'swap_enabled' => ['boolean'],
        ]);

        $shiftTemplate->update($data);

        return redirect(route('app-settings.timekeeping.index').'?sub=shifts')
            ->with('success', 'Shift template updated successfully.');
    }

    public function destroy(ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->authorizeTemplate($shiftTemplate);

        $shiftTemplate->update(['is_active' => false]);

        return redirect(route('app-settings.timekeeping.index').'?sub=shifts')
            ->with('success', 'Shift template deactivated.');
    }

    public function toggleSwap(ShiftTemplate $shiftTemplate): RedirectResponse
    {
        $this->authorizeTemplate($shiftTemplate);

        $shiftTemplate->update(['swap_enabled' => ! $shiftTemplate->swap_enabled]);

        $status = $shiftTemplate->swap_enabled ? 'enabled' : 'disabled';

        return redirect(route('app-settings.timekeeping.index').'?sub=shifts')
            ->with('success', "Shift swap {$status} for {$shiftTemplate->name}.");
    }

    private function authorizeTemplate(ShiftTemplate $shiftTemplate): void
    {
        $companyId = Auth::user()->employee?->company_id;

        if ((int) $shiftTemplate->company_id !== (int) $companyId) {
            abort(403);
        }
    }
}
